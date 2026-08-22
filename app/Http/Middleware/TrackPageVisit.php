<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records staff/admin page views inside the /manage area for the usage-analytics
 * screen. Runs in terminate() so it adds no latency to the response, and is
 * deliberately narrow: GET only, successful page responses only, and JSON polls
 * (notification/message counts) are skipped because they ask for JSON.
 */
class TrackPageVisit
{
    // Routes that are technically GET manage pages but are noise to log.
    private const IGNORED_ROUTE_SUFFIXES = ['unread-count', 'mark-read'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $request->isMethod('GET') || $request->wantsJson()) {
                return;
            }
            if ($response->getStatusCode() >= 400 || $response->isRedirection()) {
                return;
            }

            $user = $request->user();
            if (! $user || ! ($user->is_staff || $user->is_admin)) {
                return;
            }

            $routeName = $request->route()?->getName();
            foreach (self::IGNORED_ROUTE_SUFFIXES as $suffix) {
                if ($routeName && str_ends_with($routeName, $suffix)) {
                    return;
                }
            }

            PageVisit::create([
                'user_id'    => $user->id,
                'route_name' => $routeName,
                'path'       => mb_substr($request->path(), 0, 512),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                'visited_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Tracking must never break a request; swallow and move on.
        }
    }
}
