<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->level, ['admin', 'super_admin'], true)) {
            throw new AccessDeniedHttpException('You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
