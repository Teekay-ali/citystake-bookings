<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff extends Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || (!$user->is_staff && !$user->is_admin)) {
            abort(403, 'Staff access required.');
        }

        if (!$user->is_active) {
            abort(403, 'Your account has been deactivated.');
        }

        return $next($request);
    }
}
