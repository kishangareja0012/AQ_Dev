<?php

namespace App\Http\Middleware;
use Closure;

class UserActive
{
	public function handle($request, Closure $next)
	{
		if (\Auth::check()) {
			// The user is logged in...
			//$user = \Auth::user();
			//$user->lastActive = date('d-m-Y H:i:s');
			//$user->save();
		}
		return $next($request);
	}
}