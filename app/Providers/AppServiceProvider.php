<?php

namespace App\Providers;

use App\Listeners\InlineEmailCss;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        // Inline CSS on every outgoing email so styles survive clients that
        // strip <head><style> blocks.
        Event::listen(MessageSending::class, InlineEmailCss::class);
    }
}
