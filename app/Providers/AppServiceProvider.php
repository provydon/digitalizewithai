<?php

namespace App\Providers;

use App\Ai\NovaGateway;
use App\Ai\NovaProvider;
use App\Listeners\LogAiProviderAndModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Ai\Ai;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerAiRequestLogging();
        $this->registerNovaDriver();
    }

    /**
     * Register the Nova API driver (api.nova.amazon.com Chat Completions API).
     * Prism's OpenAI driver uses /v1/responses which Nova does not support.
     */
    protected function registerNovaDriver(): void
    {
        Ai::extend('nova', function ($app, array $config) {
            $baseUrl = $config['url'] ?? 'https://api.nova.amazon.com/v1';
            $key = $config['key'] ?? '';

            $client = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->contentType('application/json');

            $gateway = new NovaGateway($client, $key, $baseUrl);

            return new NovaProvider(
                $gateway,
                $config,
                $app->make(\Illuminate\Contracts\Events\Dispatcher::class)
            );
        });
    }

    /**
     * Log AI provider and model for every AI request (prompt and stream).
     */
    protected function registerAiRequestLogging(): void
    {
        $listener = LogAiProviderAndModel::class;

        Event::listen(PromptingAgent::class, $listener);
        Event::listen(StreamingAgent::class, $listener);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
