<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ClearanceMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {        
        if (Auth::user()->hasPermissionTo('Administer roles & permissions')) //If user has this //permission
    {
            return $next($request);
        }

        if ($request->is('quotes/create'))//If user is creating a quote
         {
            if (!Auth::user()->hasPermissionTo('Create Quote'))
         {
                abort('401');
            } 
         else {
                return $next($request);
            }
        }

        if ($request->is('quotes/*/edit')) //If user is editing a quote
         {
            if (!Auth::user()->hasPermissionTo('Edit Quote')) {
                abort('401');
            } else {
                return $next($request);
            }
        }

        if ($request->isMethod('Delete')) //If user is deleting a quote
         {
            if (!Auth::user()->hasPermissionTo('Delete Quote')) {
                abort('401');
            } 
         else 
         {
                return $next($request);
            }
        }

        return $next($request);
    }
}