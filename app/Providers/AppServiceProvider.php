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
        // NOTE: Vite::prefetch() is intentionally disabled. On shared hosting it
        // fires ~100 asset requests at once, tripping the concurrency limit and
        // returning 503s. Pages lazy-load their own chunks on navigation instead.

        // Inline CSS on every outgoing email so styles survive clients that
        // strip <head><style> blocks.
        Event::listen(MessageSending::class, InlineEmailCss::class);
    }
}
