<?php

namespace App\Ai\Providers;

use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Providers\Concerns\GeneratesText;
use Laravel\Ai\Providers\Concerns\HasTextGateway;
use Laravel\Ai\Providers\Concerns\StreamsText;
use Laravel\Ai\Providers\Provider;

/**
 * Amazon Nova text provider (api.nova.amazon.com).
 *
 * Uses the Chat Completions API. Registered via Ai::extend('nova') in AppServiceProvider
 * because Nova does not support Prism's Responses API used by the built-in OpenAI driver.
 *
 * @see https://nova.amazon.com/dev/documentation
 */
class NovaProvider extends Provider implements TextProvider
{
    use GeneratesText;
    use HasTextGateway;
    use StreamsText;

    /**
     * Get the name of the default text model.
     */
    public function defaultTextModel(): string
    {
        return $this->config['models']['text']['default'] ?? 'nova-2-lite-v1';
    }

    /**
     * Get the name of the cheapest text model.
     */
    public function cheapestTextModel(): string
    {
        return $this->config['models']['text']['cheapest'] ?? 'nova-micro-v1';
    }

    /**
     * Get the name of the smartest text model.
     */
    public function smartestTextModel(): string
    {
        return $this->config['models']['text']['smartest'] ?? 'nova-pro-v1';
    }
}
