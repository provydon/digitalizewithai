<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Prompts\AgentPrompt;

class LogAiProviderAndModel
{
    /**
     * Log the AI provider and model for every prompt and stream request.
     */
    public function handle(PromptingAgent|StreamingAgent $event): void
    {
        $prompt = $event->prompt;

        if (! $prompt instanceof AgentPrompt) {
            return;
        }

        $provider = $prompt->provider;
        try {
            $providerName = null;
            if (is_object($provider) && method_exists($provider, 'name')) {
                $providerName = $provider->name();
            }
            $providerName = ($providerName !== null && $providerName !== '') ? $providerName : (is_object($provider) ? class_basename($provider) : 'unknown');
        } catch (\Throwable) {
            $providerName = is_object($provider) ? class_basename($provider) : 'unknown';
        }
        $model = $prompt->model;
        $type = $event instanceof StreamingAgent ? 'stream' : 'prompt';

        Log::info('AI request', [
            'provider' => $providerName,
            'model' => $model,
            'type' => $type,
            'agent' => class_basename($prompt->agent),
        ]);
    }
}
