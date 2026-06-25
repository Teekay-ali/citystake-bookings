<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Inlines <style> CSS into element style="" attributes on every outgoing HTML
 * email. Many email clients strip <head><style> blocks, which left our
 * class-based templates rendering as unstyled plain text. Inlining makes the
 * styling render consistently across Gmail, Outlook, Apple Mail, etc.
 */
class InlineEmailCss
{
    public function handle(MessageSending $event): void
    {
        $message = $event->message;
        $html = $message->getHtmlBody();

        if (! is_string($html) || $html === '') {
            return;
        }

        try {
            $message->html((new CssToInlineStyles())->convert($html));
        } catch (\Throwable $e) {
            // Never block sending an email because inlining failed
            \Log::warning('Email CSS inlining failed', ['error' => $e->getMessage()]);
        }
    }
}
