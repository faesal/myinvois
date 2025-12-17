<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\CentralLogics\Helpers;

class RestaurantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {

        if (Auth::guard('restaurant')->check()) {
            if(auth('restaurant')->user()->is_banned == 'enable' || auth('restaurant')->user()->admin_approval != 'enable') {
                auth()->guard('restaurant')->logout();
                return redirect()->route('restaurant.login');
            }
            return $next($request);
        }

        return redirect()->route('restaurant.login');
    }
}
