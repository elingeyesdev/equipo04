<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureApiAuthority
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = (array) $request->session()->get('api_user', []);
        $role = (string) ($user['role'] ?? '');

        if ($role !== 'authority') {
            abort(403);
        }

        return $next($request);
    }
}
