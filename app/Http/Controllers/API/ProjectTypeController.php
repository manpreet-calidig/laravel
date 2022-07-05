<?php

namespace App\Http\Controllers\API;
use App\Models\User;
use App\Models\Role;
use App\Models\ProjectType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;

use DB;

class ProjectTypeController extends BaseController
{
   public function index() {
    $Projecttype = ProjectType::select('id','project_type as name')->where('id','!=',0)->paginate(20);
    return $this->sendResponse($Projecttype,'project type list');
   }

   public function project_type(Request $request){
		$validator = Validator::make($request->all(), [
			'project_type' => 'required',
		  ]);
	
		  if($validator->fails()){
			return $this->sendError('Validation Error.', $validator->errors());       
		}

		$project_type = new ProjectType;
		$project_type->project_type = $request->project_type;
		$project_type->save();

		return $this->sendResponse($project_type,'project type list');

   }
   public function destroy($id)
   {
       $project_type = ProjectType::find($id);
       $project_type->delete();

       return response()->json($project_type);
   }

   public function edit($id)
   {
       $project_type = ProjectType::find($id);


       if (is_null($project_type)) {
           return $this->sendError('Project type not found.');
       }
       return response()->json($project_type);
   }

   public function update(Request $request, $id)
   {
      
       $input = $request->all();

       $validator = Validator::make($input, [
           'project_type' => 'required',
       ]);


       if($validator->fails()){
           return $this->sendError('Validation Error.', $validator->errors());       
       }


       $project_type = ProjectType::find($id);

       $project_type->project_type = $request->project_type;
       $project_type->save();

       return response()->json($project_type);
   }
}
