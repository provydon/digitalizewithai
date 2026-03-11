<?php

namespace App\Providers;

use App\Ai\Gateway\NovaGateway;
use App\Ai\Providers\NovaProvider;
use App\Listeners\LogAiProviderAndModel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Ai\AiManager;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->afterResolving(AiManager::class, function (AiManager $manager): void {
            $this->registerNovaDriverOn($manager);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerAiRequestLogging();
    }

    /**
     * Add the Nova driver to a given AiManager instance. Called via afterResolving
     * so every scoped instance (including the one created per queued job) gets it.
     */
    protected function registerNovaDriverOn(AiManager $manager): void
    {
        $manager->extend('nova', function ($app, array $config) {
            $timeout = (int) ($config['request_timeout'] ?? config('ai.request_timeout', 600));
            $client = Http::timeout($timeout);
            $gateway = new NovaGateway(
                $client,
                $config['key'] ?? '',
                $config['url'] ?? null,
                $config
            );

            return new NovaProvider(
                $gateway,
                $config,
                $app->make(Dispatcher::class)
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
