<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TextExtractorService;
use App\Services\SearchService;
use App\Services\DocumentService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TextExtractorService::class);
        $this->app->singleton(SearchService::class);
        $this->app->singleton(DocumentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
