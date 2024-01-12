<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;
        // $allowedOrigins = ['http://localhost','http://localhost:8100','http://localhost:8101'];
        // $origin = $_SERVER['HTTP_ORIGIN'];
    
        // if (in_array($origin, $allowedOrigins)) {
        //     return $next($request)
        //         ->header('Access-Control-Allow-Origin', $origin)
        //         ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
        //         ->header('Access-Control-Allow-Headers', 'Content-Type');
        // }
    

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
