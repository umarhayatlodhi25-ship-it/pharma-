<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        // Flatten roles if passed as variadic or comma-separated string
        $allowedRoles = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $subRole) {
                $allowedRoles[] = trim($subRole);
            }
        }

        // Admin always has access, or if user's role is explicitly allowed
        if ($user->role === 'admin' || in_array($user->role, $allowedRoles, true)) {
            return $next($request);
        }

        // Determine safe fallback destination based on user role to avoid infinite redirect loops
        $fallbackUrl = match ($user->role) {
            'cashier' => '/pos',
            'hospital' => '/opd/tokens',
            default => '/dashboard',
        };

        // If user is already on their fallback URL and still unauthorized, abort to prevent loop
        if ($request->is(ltrim($fallbackUrl, '/'))) {
            abort(403, "Access Restricted: You do not have permission to access that section.");
        }

        return redirect($fallbackUrl)->with('error', "Access Restricted: You do not have permission to access that section.");
    }
}