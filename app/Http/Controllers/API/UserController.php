<?php

namespace App\Http\Controllers\API;
use App\Models\EmployeeRole;
use App\Models\Role;
use App\Models\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class UserController extends BaseController
{
   public function index() {
      $response = EmployeeRole::paginate(20);
	  return $this->sendResponse($response,'Role List');
   }
   public function rolelist(){
	   
	  $response = Role::get();
	  return $this->sendResponse($response,'Role List');
   }
   public function create(Request $request) { 
	$data = [
		"role"=>$request->name
	];

	EmployeeRole::create($data);
	$user_id = DB::getPdo()->lastInsertId();
	
	if(!empty($request->module)){
		$string = $request->module;
		$ex = explode(',',$string);
		$array = [];
		foreach($ex as $key=>$value){
			$explode = explode('-',$value);
			$array[$explode[0]][] = $explode[1];
		}
		
		$roles = Role::get();
		
		foreach($roles as $role){
			$per = "";
			if(!empty($array[$role->id])){	
				$per = implode(",",$array[$role->id]);
			}
			$pdata = [
				"role_id"=>$role->id,
				"permission"=>$per,
				"user_id"=>$user_id
			];	
			Permission::create($pdata);
		}
		
	}
	
    return $this->sendResponse('','User create successfully');
   }
   public function store($id) {
      $response = EmployeeRole::where('id',$id)->first();
	  $response = json_decode(json_encode($response), true);
	  
	  $permissions = Permission::where('user_id',$response['id'])->get();
	  $pre = [];
	  foreach($permissions as $permission){
		$explode = explode(",",$permission->permission);
		if(!empty($explode)){
			foreach($explode as $key=>$value){
				$pre[] = $permission->role_id."-".$value;
			}
		}
	  }
	  $response['permission'] = implode(",",$pre);
	  $response['module'] = $pre;
	  return $this->sendResponse($response,'User Info');
   }
   
   public function edit($id) {
      echo 'edit';
   }
   public function update(Request $request, $id) {
	
      $user = EmployeeRole::find($id);
	  $user->role = $request->name;
	  $user->save();
	  
	  
	  if(!empty($request->module)){
		$string = $request->module;
		$ex = explode(',',$string);
		$array = [];
		foreach($ex as $key=>$value){
			$explode = explode('-',$value);
			$array[$explode[0]][] = $explode[1];
		}
		
		$permissions = Permission::where('user_id',$id)->get();
		
		foreach($permissions as $permiss){
			$per = "";
			if(!empty($array[$permiss->role_id])){	
				$per = implode(",",$array[$permiss->role_id]);
			}
			
			$permission = Permission::find($permiss->id);
			$permission->permission = $per;
			$permission->save();
		}
	}
	return $this->sendResponse('','Role update successfully');
   }
   public function destroy($id) {
      EmployeeRole::find($id)->delete();
      Permission::where('user_id',$id)->delete();
	  return $this->sendResponse('','Role delete successfully');
   }
   
   public function employeerole(Request $request) {
      $response = EmployeeRole::get();
	  return $this->sendResponse($response,'Role List');
   }
}
