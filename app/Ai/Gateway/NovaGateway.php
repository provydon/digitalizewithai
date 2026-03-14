<?php

namespace App\Ai\Gateway;

use Closure;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Contracts\Files\TranscribableAudio;
use Laravel\Ai\Contracts\Gateway\Gateway;
use Laravel\Ai\Contracts\Providers\AudioProvider;
use Laravel\Ai\Contracts\Providers\EmbeddingProvider;
use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Providers\TranscriptionProvider;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Providers\Provider;
use Laravel\Ai\Responses\AudioResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\EmbeddingsResponse;
use Laravel\Ai\Responses\ImageResponse;
use Laravel\Ai\Responses\StructuredTextResponse;
use Laravel\Ai\Responses\TextResponse;
use Laravel\Ai\Responses\TranscriptionResponse;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Laravel\Ai\Streaming\Events\TextStart;
use Laravel\Ai\Tools\Request;
use LogicException;

/**
 * Gateway for Amazon Nova API (api.nova.amazon.com) using the Chat Completions endpoint.
 * Prism's OpenAI driver uses the Responses API (/v1/responses) which Nova does not support.
 *
 * Fundamental limitation: Nova has no API-level structured output (no response_format/json_schema).
 * Other providers (e.g. OpenAI) can enforce a JSON schema at the API, so the model is constrained
 * to valid schema output. With Nova we only append schema as text in the system prompt; output
 * remains free-form. That is why we need: (1) unwrapStructuredContent for markdown-wrapped JSON,
 * (2) a Nova-specific agent with stricter table vs doc rules, and (3) explicit "extract verbatim"
 * instructions—otherwise Nova may paraphrase, substitute placeholder text, or ignore structure.
 *
 * @see https://nova.amazon.com/dev/documentation
 * @see https://docs.aws.amazon.com/nova/latest/userguide/prompting-structured-output.html
 */
class NovaGateway implements Gateway
{
    protected string $baseUrl = 'https://api.nova.amazon.com/v1';

    protected Closure $invokingToolCallback;

    protected Closure $toolInvokedCallback;

    public function __construct(
        protected PendingRequest $client,
        protected string $apiKey,
        ?string $baseUrl = null,
        protected array $config = [],
    ) {
        $this->baseUrl = rtrim($baseUrl ?? $this->baseUrl, '/');
        $this->invokingToolCallback = fn () => true;
        $this->toolInvokedCallback = fn () => true;
    }

    public function generateText(
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages = [],
        array $tools = [],
        ?array $schema = null,
        ?TextGenerationOptions $options = null,
        ?int $timeout = null,
    ): TextResponse {
        if ($schema !== null) {
            $instructions = rtrim($instructions ?? '')."\n\n".$this->structuredOutputSchemaPrompt($schema);
            $openAiMessages = $this->toOpenAiMessages($instructions, $messages);
            $body = [
                'model' => $model,
                'messages' => $openAiMessages,
                'max_tokens' => $options?->maxTokens ?? 4096,
                'temperature' => 0,
            ];
        } else {
            $openAiMessages = $this->toOpenAiMessages($instructions, $messages);
            $body = [
                'model' => $model,
                'messages' => $openAiMessages,
                'max_tokens' => $options?->maxTokens ?? 4096,
                'temperature' => $options?->temperature,
            ];
        }
        $body = $this->mergeNovaRequestOptions($body);
        if (count($tools) > 0) {
            $body['tools'] = $this->toOpenAiTools($tools);
            $body['tool_choice'] = 'auto';
        }

        $totalInputTokens = 0;
        $totalOutputTokens = 0;
        $allAssistantMessages = [];

        $requestTimeout = $timeout ?? (int) ($this->config['request_timeout'] ?? config('ai.request_timeout', 600));

        do {
            $response = $this->client
                ->timeout($requestTimeout)
                ->withHeaders(['Authorization' => 'Bearer '.$this->apiKey])
                ->post($this->baseUrl.'/chat/completions', $body);

            $response->throw();

            $data = $response->json();
            $message = $data['choices'][0]['message'] ?? [];
            $content = $message['content'] ?? '';
            $toolCalls = $message['tool_calls'] ?? [];

            $usage = $data['usage'] ?? [];
            $totalInputTokens += (int) ($usage['prompt_tokens'] ?? 0);
            $totalOutputTokens += (int) ($usage['completion_tokens'] ?? 0);

            if (count($toolCalls) === 0) {
                break;
            }

            $openAiMessages[] = [
                'role' => 'assistant',
                'content' => $content !== '' ? $content : null,
                'tool_calls' => array_map(function (array $tc) {
                    $fn = $tc['function'] ?? [];

                    return [
                        'id' => $tc['id'] ?? '',
                        'type' => 'function',
                        'function' => [
                            'name' => $fn['name'] ?? '',
                            'arguments' => $fn['arguments'] ?? '{}',
                        ],
                    ];
                }, $toolCalls),
            ];

            $toolsByName = [];
            foreach ($tools as $tool) {
                if ($tool instanceof Tool) {
                    $toolsByName[$this->toolName($tool)] = $tool;
                }
            }

            foreach ($toolCalls as $tc) {
                $id = $tc['id'] ?? '';
                $fn = $tc['function'] ?? [];
                $name = $fn['name'] ?? '';
                $argsJson = $fn['arguments'] ?? '{}';
                $arguments = is_string($argsJson) ? (json_decode($argsJson, true) ?: []) : $argsJson;

                $tool = $toolsByName[$name] ?? null;
                if (! $tool instanceof Tool) {
                    $openAiMessages[] = ['role' => 'tool', 'content' => "Error: unknown tool \"{$name}\".", 'tool_call_id' => $id];

                    continue;
                }

                ($this->invokingToolCallback)($tool, $arguments);

                try {
                    $result = $tool->handle(new Request($arguments));
                    $resultStr = $result instanceof \Stringable || is_string($result) ? (string) $result : json_encode($result);
                } catch (\Throwable $e) {
                    $resultStr = 'Error: '.$e->getMessage();
                }

                ($this->toolInvokedCallback)($tool, $arguments, $resultStr);

                $openAiMessages[] = ['role' => 'tool', 'content' => $resultStr, 'tool_call_id' => $id];
            }

            $body['messages'] = $openAiMessages;
        } while (true);

        $usageObj = new Usage($totalInputTokens, $totalOutputTokens, 0, 0, 0);
        $meta = new Meta($this->providerName($provider), $model);

        if ($schema !== null) {
            $structured = json_decode($content, true);
            if (! is_array($structured)) {
                $structured = $this->unwrapStructuredContent($content);
            }
            if (! is_array($structured)) {
                $structured = ['content' => $content];
            }

            return (new StructuredTextResponse($structured, $content, $usageObj, $meta))
                ->withMessages(collect([new AssistantMessage($content)]));
        }

        return (new TextResponse($content, $usageObj, $meta))
            ->withMessages(collect([new AssistantMessage($content)]));
    }

    public function streamText(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages = [],
        array $tools = [],
        ?array $schema = null,
        ?TextGenerationOptions $options = null,
        ?int $timeout = null,
    ): Generator {
        $openAiMessages = $this->toOpenAiMessages($instructions, $messages);
        $body = [
            'model' => $model,
            'messages' => $openAiMessages,
            'stream' => true,
            'max_tokens' => $options?->maxTokens ?? 4096,
            'temperature' => $options?->temperature,
            'stream_options' => ['include_usage' => true],
        ];
        $body = $this->mergeNovaRequestOptions($body);
        if (count($tools) > 0) {
            $body['tools'] = $this->toOpenAiTools($tools);
            $body['tool_choice'] = 'auto';
        }

        $requestTimeout = $timeout ?? (int) ($this->config['request_timeout'] ?? config('ai.request_timeout', 600));

        $response = $this->client
            ->timeout($requestTimeout)
            ->withHeaders(['Authorization' => 'Bearer '.$this->apiKey])
            ->withOptions(['stream' => true])
            ->post($this->baseUrl.'/chat/completions', $body);

        $response->throw();

        $timestamp = time();
        $messageId = $invocationId;

        yield (new StreamStart($invocationId, $this->providerName($provider), $model, $timestamp))->withInvocationId($invocationId);
        yield (new TextStart($invocationId, $messageId, $timestamp))->withInvocationId($invocationId);

        $stream = $response->toPsrResponse()->getBody();
        $buffer = '';
        $inputTokens = 0;
        $outputTokens = 0;

        while (! $stream->eof()) {
            $buffer .= $stream->read(8192);
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines) ?: '';

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || ! str_starts_with($line, 'data: ')) {
                    continue;
                }
                $json = substr($line, 6);
                if ($json === '[DONE]') {
                    continue;
                }
                $chunk = json_decode($json, true);
                if (! is_array($chunk)) {
                    continue;
                }
                $delta = $chunk['choices'][0]['delta']['content'] ?? null;
                if (is_string($delta) && $delta !== '') {
                    yield (new TextDelta($invocationId, $messageId, $delta, $timestamp))->withInvocationId($invocationId);
                }
                if (! empty($chunk['usage'])) {
                    $inputTokens = (int) ($chunk['usage']['input_tokens'] ?? $chunk['usage']['prompt_tokens'] ?? $inputTokens);
                    $outputTokens = (int) ($chunk['usage']['output_tokens'] ?? $chunk['usage']['completion_tokens'] ?? $outputTokens);
                }
            }
        }

        yield (new TextEnd($invocationId, $messageId, $timestamp))->withInvocationId($invocationId);
        yield (new StreamEnd($invocationId, 'end_turn', new Usage($inputTokens, $outputTokens), $timestamp))->withInvocationId($invocationId);
    }

    public function onToolInvocation(Closure $invoking, Closure $invoked): self
    {
        $this->invokingToolCallback = $invoking;
        $this->toolInvokedCallback = $invoked;

        return $this;
    }

    /**
     * Get provider name for responses (TextProvider contract does not define name(); Provider does).
     */
    protected function providerName(TextProvider $provider): string
    {
        return $provider instanceof Provider ? $provider->name() : 'nova';
    }

    /**
     * Get tool name for API payloads (Tool contract does not define name(); implementations may).
     */
    protected function toolName(Tool $tool): string
    {
        return method_exists($tool, 'name') ? call_user_func([$tool, 'name']) : class_basename($tool);
    }

    /**
     * Merge Nova API request options from config (reasoning_effort, top_p).
     *
     * @see https://nova.amazon.com/dev/documentation — Request Parameters
     */
    protected function mergeNovaRequestOptions(array $body): array
    {
        $effort = $this->config['reasoning_effort'] ?? null;
        if (in_array($effort, ['disabled', 'low', 'medium', 'high'], true)) {
            $body['reasoning_effort'] = $effort;
        }
        $topP = $this->config['top_p'] ?? null;
        if (is_numeric($topP) && $topP >= 0 && $topP <= 1) {
            $body['top_p'] = (float) $topP;
        }

        return $body;
    }

    /**
     * When Nova returns "```json\n{...}\n```" as the raw content, unwrap and return the inner object.
     * Uses brace matching so content that contains "}" or "```" is handled correctly.
     *
     * @return array<string, mixed>|null
     */
    protected function unwrapStructuredContent(string $content): ?array
    {
        $stripped = preg_replace('/^\s*```(?:json)?\s*/i', '', $content);
        $start = strpos($stripped, '{');
        if ($start === false) {
            return null;
        }
        $depth = 0;
        $inString = false;
        $escape = false;
        $len = strlen($stripped);
        for ($i = $start; $i < $len; $i++) {
            $c = $stripped[$i];
            if ($escape) {
                $escape = false;

                continue;
            }
            if ($c === '\\' && $inString) {
                $escape = true;

                continue;
            }
            if ($c === '"') {
                $inString = ! $inString;

                continue;
            }
            if (! $inString) {
                if ($c === '{') {
                    $depth++;
                } elseif ($c === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $json = substr($stripped, $start, $i - $start + 1);
                        $decoded = json_decode($json, true);

                        return is_array($decoded) ? $decoded : null;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Build explicit output-schema text for Nova when structured output is requested.
     * Nova does not receive the schema in the API; adding it to the system prompt
     * improves adherence (see https://docs.aws.amazon.com/nova/latest/userguide/prompting-structured-output.html).
     *
     * @param  array<string, mixed>  $schema
     */
    protected function structuredOutputSchemaPrompt(array $schema): string
    {
        $keys = array_keys($schema);
        if (count($keys) === 0) {
            return 'Respond with valid JSON only, no other text or preamble.';
        }

        $quotedKeys = array_map(fn (string $key) => "\"{$key}\"", $keys);
        $lines = [
            'Output only a single JSON object. Do not include any preamble, markdown fences, or text before or after the JSON.',
            'Return exactly one JSON object with these keys: '.implode(', ', $quotedKeys).'.',
            'Use proper JSON types for every value. Strings must be quoted, integers must be bare numbers, booleans must be true/false, arrays must be JSON arrays, and objects must be JSON objects.',
            'Do not rename keys, omit keys, or add extra keys.',
        ];

        if (in_array('chartType', $keys, true)) {
            $lines[] = 'If "chartType" is present, it must be exactly one of: "bar", "line", or "pie".';
        }

        if (in_array('labelColumn', $keys, true) || in_array('valueColumn', $keys, true)) {
            $lines[] = 'If "labelColumn" or "valueColumn" is present, use 0-based integer column indexes.';
        }

        if (in_array('aggregation', $keys, true)) {
            $lines[] = 'If "aggregation" is present, it must be exactly one of: "none", "sum", or "count".';
        }

        if (in_array('type', $keys, true) && in_array('content', $keys, true)) {
            $lines[] = 'If "type" is present, it must be the string "doc" or "table".';
            $lines[] = 'Use type "table" for any repeated list with columns: e.g. state + abbreviation, name + value, key + value, product + price.';
            $lines[] = 'For type "table", "content" must be a JSON string of {"headers": ["Col1", "Col2", ...], "rows": [["a", "b"], ...]}.';
            $lines[] = 'Use type "doc" only for continuous prose or paragraphs, not for columnar lists.';
        }

        return implode("\n", $lines);
    }

    /**
     * Convert Laravel AI messages to OpenAI Chat Completions format.
     * Supports text and vision (user message attachments as image parts).
     *
     * @return array<int, array{role: string, content: string|array<int, array{type: string, text?: string, image_url?: array{url: string}}>}>
     */
    protected function toOpenAiMessages(?string $instructions, array $messages): array
    {
        $out = [];
        if ($instructions !== null && $instructions !== '') {
            $out[] = ['role' => 'system', 'content' => $instructions];
        }
        foreach ($messages as $message) {
            if ($message instanceof UserMessage) {
                $out[] = ['role' => 'user', 'content' => $this->userMessageContent($message)];
            } elseif ($message instanceof AssistantMessage) {
                $out[] = ['role' => 'assistant', 'content' => $message->content ?? ''];
            } elseif ($message instanceof ToolResultMessage) {
                foreach ($message->toolResults ?? [] as $tr) {
                    $out[] = [
                        'role' => 'tool',
                        'content' => is_string($tr->result) ? $tr->result : json_encode($tr->result),
                        'tool_call_id' => $tr->id,
                    ];
                }
            } else {
                $msg = Message::tryFrom($message);
                if ($msg) {
                    $role = match ($msg->role) {
                        MessageRole::User => 'user',
                        MessageRole::Assistant => 'assistant',
                        default => null,
                    };
                    if ($role !== null) {
                        $out[] = ['role' => $role, 'content' => $msg->content ?? ''];
                    }
                }
            }
        }

        return $out;
    }

    /**
     * User message content as string or array of parts (text + image_url) for vision.
     */
    protected function userMessageContent(UserMessage $message): string|array
    {
        $attachments = $message->attachments->filter(function ($att) {
            return is_object($att) && method_exists($att, 'content') && method_exists($att, 'mimeType');
        });
        if ($attachments->isEmpty()) {
            return $message->content ?? '';
        }
        $parts = [];
        if (trim((string) ($message->content ?? '')) !== '') {
            $parts[] = ['type' => 'text', 'text' => $message->content];
        }
        foreach ($attachments as $att) {
            $content = $att->content();
            $mime = $att->mimeType() ?? 'image/jpeg';
            if (is_string($content)) {
                $parts[] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => 'data:'.$mime.';base64,'.base64_encode($content)],
                ];
            }
        }

        return $parts;
    }

    /**
     * @param  array<Tool>  $tools
     * @return array<int, array{type: string, function: array{name: string, description: string, parameters: array}}>
     */
    protected function toOpenAiTools(array $tools): array
    {
        $factory = new JsonSchemaTypeFactory;
        $out = [];
        foreach ($tools as $tool) {
            if (! $tool instanceof Tool) {
                continue;
            }
            $name = $this->toolName($tool);
            $schema = $tool->schema($factory);
            $properties = [];
            $required = [];
            foreach ($schema as $key => $type) {
                $properties[$key] = ['type' => 'string', 'description' => ''];
                $required[] = $key;
            }
            $out[] = [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => (string) $tool->description(),
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $properties,
                        'required' => $required,
                    ],
                ],
            ];
        }

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function generateAudio(
        AudioProvider $provider,
        string $model,
        string $text,
        string $voice,
        ?string $instructions = null,
    ): AudioResponse {
        throw new LogicException('Nova API driver does not support audio generation.');
    }

    /**
     * {@inheritdoc}
     */
    public function generateImage(
        ImageProvider $provider,
        string $model,
        string $prompt,
        array $attachments = [],
        ?string $size = null,
        ?string $quality = null,
        ?int $timeout = null,
    ): ImageResponse {
        throw new LogicException('Nova API driver does not support image generation.');
    }

    /**
     * {@inheritdoc}
     */
    public function generateEmbeddings(
        EmbeddingProvider $provider,
        string $model,
        array $inputs,
        int $dimensions
    ): EmbeddingsResponse {
        throw new LogicException('Nova API driver does not support embeddings.');
    }

    /**
     * {@inheritdoc}
     */
    public function generateTranscription(
        TranscriptionProvider $provider,
        string $model,
        TranscribableAudio $audio,
        ?string $language = null,
        bool $diarize = false,
        int $timeout = 30
    ): TranscriptionResponse {
        throw new LogicException('Nova API driver does not support transcription.');
    }
}
