<?php

namespace App\Http\Middleware;

use App\Support\RolePreview;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role preview is strictly read-only: browsing as another role must never
 * mutate real data. Everything except safe verbs is refused while a preview
 * is active, apart from the routes needed to leave the preview or log out.
 */
class BlockWritesWhilePreviewing
{
    /** Routes that must keep working while previewing. */
    private const ALLOWED = [
        'manage.preview.exit',
        'manage.preview.store',   // switch to a different role without exiting first
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! RolePreview::active()) {
            return $next($request);
        }

        if ($request->isMethodSafe() || in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return $next($request);
        }

        $message = 'Role preview is read-only. Exit the preview to make changes.';

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        return back()->with('error', $message);
    }
}
