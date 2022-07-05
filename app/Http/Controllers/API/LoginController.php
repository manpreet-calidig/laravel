<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\EmployeeRole;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;


class LoginController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'type' => 'required',
        ]);
		
		if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
		$email = $request->email;
		$password = md5($request->password);
		$type = $request->type;
		
		if($type == 'user'){
			$response = User::where("email",$email)->where("password",$password)->first();
		}elseif($type == 'client'){
			$response = Client::where("email",$email)->where("password",$password)->first();
		}elseif($type == 'employee'){
			$response = Employee::where("email",$email)->where("password",$password)->first();
		}
        $token = Str::random(500);
		if(!empty($response)){
			$bearer = "Bearer ".$token."-".$type;
			$response->remember_token = $bearer;
			$response->save();
			$response = json_decode(json_encode($response), true);
			if($type == 'user'){
				$permissions =  Permission::select('role_id','permission')->where('user_id',1)->get();
				$permission = [];
				foreach($permissions as $perm){
					$permission[$perm->role_id] = $perm->permission;
				}
				
				$response['permission']= $permission;
			}else if($type == 'employee'){
				
				$employeerole = EmployeeRole::where('id',$response['role'])->first();
				$response['employeerole']= $employeerole->role;
				$permissions =  Permission::select('role_id','permission')->where('user_id',$response['role'])->get();
				$permission = [];
				foreach($permissions as $perm){
					$permission[$perm->role_id] = $perm->permission;
				}
				
				$response['permission']= $permission;
			}
			
			$response['remember_token']= $bearer;
			$response['type']= $type;
			$resourceInfo = $response;
			$msg = "Login successfully ";
			return $this->sendResponse($resourceInfo,$msg);
		}else{
			$resourceInfo = [];
			$msg = "Invalid login detail";
			return $this->sendError('',$msg);
		}
    }
}
