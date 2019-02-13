<?php

namespace App\Http\Middleware;

use Closure;

class VkToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! \App\Vk\Token::active()->first()) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}
