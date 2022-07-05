<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Notification;
use App\Models\LeaveBalance;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;
use DB;


class PasswordController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */

    public function store(request $request){
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'confirm_password' => 'required|same:password',
          ]);
          if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        
        $type = $request->type;
        $id = $request->id;
        $password = $request->password;
        if($type == 'employee')
        {
            $employee = Employee::where('id',$id)->first();
            $employee->password = $password;
            $employee->save();
            return $this->sendResponse($employee,'password list');
        }

        if($type == 'client')
        {
            $client = Client::where('id',$id)->first();
            $client->password = $password;
            $client->save();
            return $this->sendResponse($client,'password list');
        }

        if($type == 'user')
        {
            $user = User::where('id',$id)->first();
            $user->password = $password;
            $user->save();
            return $this->sendResponse($user,'password list');
        }
    }
   
}
