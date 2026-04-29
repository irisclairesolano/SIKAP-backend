<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = Auth::user();

        abort_if(!$user, 401, 'Unauthenticated.');
        abort_if($user->is_suspended, 403, 'Account suspended.');
        abort_if(!in_array($user->role, $roles), 403, 'Access denied. Required role: ' . implode(' or ', $roles));

        return $next($request);
    }
}
