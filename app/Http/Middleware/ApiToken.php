<?php

namespace App\Http\Middleware;
use Closure;

class ApiToken
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array
     */
     public function handle($request, Closure $next, $guard = null)
    {
        $headers = apache_request_headers();
		if (isset($headers['Authorization']) || isset($headers['authorization'])) {
		if (isset($headers['Authorization'])){
			$api_key = $headers['Authorization'];
			} else {
			$api_key = $headers['authorization'];
			}
			if($api_key != API_SEC_KEY) {
				$response["error"] = true;
            	$response["message"] = "Access Denied. Invalid Api key";
				echo json_encode($response);
				exit;
			}
		} else {
        // api key is missing in header
			$response["error"] = true;
			$response["message"] = "Api key is misssing";
			echo json_encode($response);
			exit;
    }
        
		
		return $next($request);
    }
}
