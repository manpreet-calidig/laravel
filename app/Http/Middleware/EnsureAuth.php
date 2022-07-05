<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;

class EnsureAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		$reexplode = explode("-",$headers['Authorization']);
		$end = end($reexplode);
		if($end == 'user'){
			$response = User::where('remember_token',$headers['Authorization'])->first();
		}elseif($end == 'client'){
			$response = Client::where('remember_token',$headers['Authorization'])->first();
		}elseif($end == 'employee'){
			$response = Employee::where('remember_token',$headers['Authorization'])->first();
		}
		
		if(!empty($response)) {
			if($response->status == 'D'){
				return response()->json(['status' => 401,'message' => "Unauthenticated"],401);
			}
			$request->attributes->add(['user_id' => $response->id,'type' =>$end]);
            return $next($request);
		}else{
			return response()->json(['status' => 200,'message' => "Unauthenticated"],200);
        }
        return $next($request);
    }
}