<?php

namespace App\Providers;

use App\Services\Ai\AgnoEvaluationService;
use App\Services\Ai\Contracts\AiEvaluationServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiEvaluationServiceInterface::class, AgnoEvaluationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
