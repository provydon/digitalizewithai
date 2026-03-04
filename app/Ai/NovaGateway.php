<?php

namespace App\Ai;

use Closure;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Gateway\Gateway;
use Laravel\Ai\Contracts\Gateway\TextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Laravel\Ai\Responses\TextResponse;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Laravel\Ai\Streaming\Events\TextStart;
use Laravel\Ai\Tools\Request as ToolRequest;
use Throwable;

/**
 * Gateway for Amazon Nova API (api.nova.amazon.com) using the Chat Completions endpoint.
 * Prism's OpenAI driver uses the Responses API (/v1/responses) which Nova does not support.
 *
 * @see https://nova.amazon.com/dev/documentation
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
        $instructions = $schema !== null
            ? rtrim($instructions ?? '')."\n\nRespond with valid JSON only, no other text."
            : $instructions;
        $openAiMessages = $this->toOpenAiMessages($instructions, $messages);
        $body = [
            'model' => $model,
            'messages' => $openAiMessages,
            'max_tokens' => $options?->maxTokens ?? 4096,
            'temperature' => $options?->temperature,
        ];
        if (count($tools) > 0) {
            $body['tools'] = $this->toOpenAiTools($tools);
            $body['tool_choice'] = 'auto';
        }

        $response = $this->client
            ->timeout($timeout ?? 60)
            ->withHeaders(['Authorization' => 'Bearer '.$this->apiKey])
            ->post($this->baseUrl.'/chat/completions', $body);

        $response->throw();

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];
        $usageObj = new Usage(
            (int) ($usage['prompt_tokens'] ?? 0),
            (int) ($usage['completion_tokens'] ?? 0),
            0,
            0,
            0,
        );

        $meta = new Meta($provider->name(), $model);

        if ($schema !== null) {
            $structured = json_decode($content, true);
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
        ];
        if (count($tools) > 0) {
            $body['tools'] = $this->toOpenAiTools($tools);
            $body['tool_choice'] = 'auto';
        }

        $response = $this->client
            ->timeout($timeout ?? 60)
            ->withHeaders(['Authorization' => 'Bearer '.$this->apiKey])
            ->withOptions(['stream' => true])
            ->post($this->baseUrl.'/chat/completions', $body);

        $response->throw();

        $timestamp = time();
        $messageId = $invocationId;

        yield (new StreamStart($invocationId, $provider->name(), $model, $timestamp))->withInvocationId($invocationId);
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
                    $inputTokens = (int) ($chunk['usage']['input_tokens'] ?? $inputTokens);
                    $outputTokens = (int) ($chunk['usage']['output_tokens'] ?? $outputTokens);
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
     * @return array<int, array{role: string, content: string}>
     */
    protected function toOpenAiMessages(?string $instructions, array $messages): array
    {
        $out = [];
        if ($instructions !== null && $instructions !== '') {
            $out[] = ['role' => 'system', 'content' => $instructions];
        }
        foreach ($messages as $message) {
            if ($message instanceof UserMessage) {
                $out[] = ['role' => 'user', 'content' => $message->content ?? ''];
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
     * @param  array<Tool>  $tools
     * @return array<int, array{type: string, function: array{name: string, description: string, parameters: array}}>
     */
    protected function toOpenAiTools(array $tools): array
    {
        $factory = new \Illuminate\JsonSchema\JsonSchemaTypeFactory;
        $out = [];
        foreach ($tools as $tool) {
            if (! $tool instanceof Tool) {
                continue;
            }
            $name = method_exists($tool, 'name') ? $tool->name() : class_basename($tool);
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

    // Gateway stubs (Nova API is text/chat only via this driver)

    public function generateAudio(
        \Laravel\Ai\Contracts\Providers\AudioProvider $provider,
        string $model,
        string $text,
        string $voice,
        ?string $instructions = null,
    ): \Laravel\Ai\Responses\AudioResponse {
        throw new \LogicException('Nova API driver does not support audio generation.');
    }

    public function generateImage(
        \Laravel\Ai\Contracts\Providers\ImageProvider $provider,
        string $model,
        string $prompt,
        array $attachments = [],
        ?string $size = null,
        ?string $quality = null,
        ?int $timeout = null,
    ): \Laravel\Ai\Responses\ImageResponse {
        throw new \LogicException('Nova API driver does not support image generation.');
    }

    public function generateEmbeddings(
        \Laravel\Ai\Contracts\Providers\EmbeddingProvider $provider,
        string $model,
        array $inputs,
        int $dimensions
    ): \Laravel\Ai\Responses\EmbeddingsResponse {
        throw new \LogicException('Nova API driver does not support embeddings.');
    }

    public function generateTranscription(
        \Laravel\Ai\Contracts\Providers\TranscriptionProvider $provider,
        string $model,
        \Laravel\Ai\Contracts\Files\TranscribableAudio $audio,
        ?string $language = null,
        bool $diarize = false,
        int $timeout = 30
    ): \Laravel\Ai\Responses\TranscriptionResponse {
        throw new \LogicException('Nova API driver does not support transcription.');
    }
}
