<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExtendTokenExpiration
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->forceFill(['expires_at' => now()->addHour()])->save();
        }

        return $next($request);
    }
}
