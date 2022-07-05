<?php

namespace App\Http\Controllers\API;
use App\Models\User;
use App\Models\Role;
use App\Models\Project;
use App\Models\Employee;
use App\Models\EmployeeAvailable;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Mail;

use DB;

class ProjectController extends BaseController
{
	public function index(Request $request) {
		$search = $request->search;
		$pagevalue = $request->pagevalue;
		$project = Project::where('id','!=',0);
		if(!empty($search)){
			$project = $project->where('project_name', 'LIKE', "%{$search}%")
			->orWhere('client_name', 'LIKE', "%{$search}%");
		}
		if(!empty($pagevalue)){
			$project = $project->paginate($pagevalue);
		}else{
			$project = $project->paginate(20);
		}
		foreach($project as $pro) {
			$document= explode(",",$pro->upload_document);
			$documentlist = [];
			foreach($document as $key=>$value) {
				$documentlist[] = env('APP_URL').'files/'.$value;
			}

			$pro->upload_document = $documentlist;
		}
		return $this->sendResponse($project,'Project list');
	}

	public function employeeproject(Request $request, $id){
		// print_r($id);
		$search = $request->search;
		$pagevalue = $request->pagevalue;
		$a = Project::whereRaw('FIND_IN_SET(?,assign_employee)', [$id]);

		$project = Project::where('project_name', '=', 'Calidig-Internal')
							->union($a);
			if(!empty($search)){
				$project = $project->where('project_name', 'LIKE', "%{$search}%")
									->orWhere('client_name', 'LIKE', "%{$search}%");
			}
			if(!empty($pagevalue)){
				$project = $project->paginate($pagevalue);
			}else{
				$project = $project->paginate(20);
			}
		foreach($project as $pro){
			$pro->en_id = $this->encrypt($pro->id);
			$document= explode(",",$pro->upload_document);
			$documentlist = [];
			foreach($document as $key=>$value) {
				$documentlist[] = env('APP_URL').'files/'.$value;
			}

			$pro->upload_document = $documentlist;
		}
		return $this->sendResponse($project,'Employee Project list');
	}

	public function clientproject(Request $request, $id) {
		$search = $request->search;
		$pagevalue = $request->pagevalue;

		$project = Project::where('client_id','=',$id);
		if(!empty($search)){
			$project = $project->where('project_name', 'LIKE', "%{$search}%")
								->orWhere('client_name', 'LIKE', "%{$search}%");
		}
		if(!empty($pagevalue)){
			$project = $project->paginate($pagevalue);
		}else{
			$project = $project->paginate(20);
		}
		foreach($project as $pro){
			$pro->en_id = $this->encrypt($pro->id);
		}
		return $this->sendResponse($project,'Client Project list');
	}

   public function addproject(Request $request) {
	$validator = Validator::make($request->all(), [
		'project_name' => 'required',
		'client_id' => 'required',
		'client_name' => 'required',
		'client_email' => 'required',
		'start_date' => 'required',
		'end_date' => 'required',
		'team_size' => 'required',
		'assign_employee' => 'required',
		'project_type' => 'required',
	  ]);

	  if($validator->fails()){
		return $this->sendError('Validation Error.', $validator->errors());
	}

	$id = $request->assign_employee;
	$employee = Employee::whereIn('id',$id)->get();


		$project = new Project;

		$project->project_name = $request->project_name;
		$project->client_id = $request->client_id;
		$project->client_email = $request->client_email;
		$project->client_name = $request->client_name;
		$project->client_email = $request->client_email;
		$project->start_date = $request->start_date;
		$project->end_date = $request->end_date;
		$project->team_size = $request->team_size;
		$project->assign_employee = implode(",",$request->assign_employee);
		$project->project_type = $request->project_type;
		$project->hour = $request->hour;
		$project->status = $request->status;

		$project->save();
		$project_id = $project->id;

		foreach ($request->items as $value){
				$emp = new EmployeeAvailable;
				$emp->employee_id = $value['employee_id'];
				$emp->project_id = $project_id;
				$emp->allocation = $value['allocation'];
				$emp->save();
		}


		foreach($employee as $emp)
		{
			$email = $emp->email;
			$firstname = $emp->first_name;
			$lastname = $emp->last_name;

			$data = [
				'subject' => 'New project:'.$request->project_name,
				'name' => $firstname. ' ' .$lastname,
				'email' => $email,
				'date' => $request->start_date. ' to ' .$request->end_date,
				'url' => env('SITE_URL')
			  ];
			  if (strpos($data['email'], 'calidig.com') !== false) {
				$data['email'] = $data['email'].'.test-google-a.com';
			}
			  Mail::send('email/project', $data, function($message) use ($data) {
				$message->to($data['email'])
				->subject($data['subject']);
				$message->from('noreply@calidig.com','No Reply');
			  });
		}


		return $this->sendResponse($project,'project type list');
   }

   public function empvalue(request $request)
   {
	//    $id = $request->value;
	   $value = explode(",", $request->value);

	// $exassign = array_map('intval', explode(',', $request->value));
	$project = Employee::whereIn('id', $value)->get();
	$date = date('Y-m-d');
	$items = [];

	foreach ($project as $value){
		// print_r($value);
		$empproject = EmployeeAvailable::where('employee_id',$value->id)->get();
		$total = 0;
		$allocation = [];
		$test = '';
		foreach($empproject as $emp){
			$empdate = date('Y-m-d', strtotime($emp->created_at));
			array_push($allocation,$emp->allocation);
			if($empdate <= $date){
				$test = 'false';
			}else{
				$test = 'true';
			}
		}

		foreach($allocation as $data){
			$total += $data;
		}

		if($test == 'false'){
			$value->availability = 100-$total;
		}else{
			$value->availability = 100;
		}

		if($value->availability < 0)
		{
			$value->availability = 0;
		}
		if($value->allocation == null)
		{
			$value->allocation = '';
		}

		// echo $empdate;
		$items[] = ['employee_id'=>$value->id,'availability'=>$value->availability,'allocation'=>$value->allocation,'name'=>$value->first_name.' '.$value->last_name];
	}
		// $project['items'] = $items;

	return response()->json($items);


   }

   public function destroy($id)
   {
       $project = Project::find($id);
       $project->delete();

       return response()->json($project);
   }

   public function edit($id)
    {
        $project = Project::find($id);
        if (!empty($project)) {
			// $exassign = explode(",",$project->assign_employee);
			$exassign = array_map('intval', explode(',', $project->assign_employee));

			// $employee = Employee::select('id',DB::raw('CONCAT(first_name,\' \',last_name) AS name'))->whereIn('id', $exassign)->get();
			if (!empty($exassign)) {
				$project->assign_employee = $exassign;
			}else{
				$project->assign_employee = "";
			}
			$date = date('Y-m-d');
			$empproject = EmployeeAvailable::where('project_id',$id)->get();
			
			if($project->project_name == 'Calidig-Internal'){
				$projects = Employee::all();
			}else{
				$projects = Employee::whereIn('id', $exassign)->get();
			}
			$items = [];

			foreach ($projects as $value){
				$empprojects = EmployeeAvailable::where('employee_id',$value->id)->get();
				$total = 0;
				$allocation = [];
				$test = '';
				foreach($empprojects as $emp){
					$empdate = date('Y-m-d', strtotime($emp->created_at));
					array_push($allocation,$emp->allocation);
					if($empdate <= $date){
						$test = 'false';
					}else{
						$test = 'true';
					}	
				}

				foreach($allocation as $data){
					$total += $data;
				}
				if($test == 'false'){
					$value->availability = 100-$total;
				}else{
					$value->availability = 100;
				}
		
				if($value->availability < 0)
				{
					$value->availability = 0;
				}
				$items[] = ['id'=>$id,'employee_id'=>$value->id,'availability'=>$value->availability,'allocation'=>$value->allocation,'name'=>$value->first_name.' '.$value->last_name];

			}

			$response[] = ['project'=>$project,'empavailble'=>$items];
			return response()->json($response);
        }
        return $this->sendError('project not found.');
	}

	public function update(Request $request, $id)
    {

        $input = $request->all();

        $validator = Validator::make($input, [
		'project_name' => 'required',
		'client_id' => 'required',
		'client_name' => 'required',
		'client_email' => 'required',
		'start_date' => 'required',
		'end_date' => 'required',
		'team_size' => 'required',
		'assign_employee' => 'required',
		'project_type' => 'required',
        ]);


        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }


        $project = Project::find($id);

        $project->project_name = $request->project_name;
		$project->client_id = $request->client_id;
		$project->client_email = $request->client_email;
		$project->client_name = $request->client_name;
		$project->client_email = $request->client_email;
		$project->start_date = $request->start_date;
		$project->end_date = $request->end_date;
		$project->team_size = $request->team_size;
		$project->assign_employee = implode(",",$request->assign_employee);
		$project->project_type = $request->project_type;
		$project->status = $request->status;
		$project->hour = $request->hour;

		$project->save();
		$pid = $project->id;

		foreach ($request->items as $value){
			$emp = new EmployeeAvailable;
			$emp->project_id = $pid;
			$emp->employee_id = $value['employee_id'];
			$emp->allocation = $value['allocation'];
			$emp->save();
			}
		return $this->sendResponse($project,'project update');

	}

	public function document(Request $request, $id){
		$data = [];
		$oldfile = $request->oldfiles;
		if($request->hasfile('file'))
        {
			$i = 0;
            foreach($request->file('file') as $file)
            {
				$filenameWithExt = $file->getClientOriginalName();
				$filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $name = $filename.$i.time().'.'.$file->extension();
                $file->move(public_path().'/files/', $name);
                $data[] = $name;
				$i++;
            }
        }
		$files = implode(",",$data);
		$project = Project::find($id);
		$project->upload_document = ((!empty($files))?$files:'').((!empty($oldfile))?",".$oldfile:'');
		$project->save();
		return response()->json($project);
	}

    public function getProjectById($id) {
        $project = Project::find($id)->first();
        return response()->json($project);
    }
}
