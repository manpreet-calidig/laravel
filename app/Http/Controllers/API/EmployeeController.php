<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Skill;
use App\Models\EmployeeAvailable;


use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;
use DB;
use Mail;
use Carbon\Carbon;


class EmployeeController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */


     public function employeelist(){
        $today = Carbon::now()->format('Y-m-d');
		$employee = Employee::select('id',DB::raw('CONCAT(first_name,\' \',last_name) AS name'))->where('last_working_day', '>=' , $today)
          ->orWhere('last_working_day', null)->get();
        return $this->sendResponse($employee,'Employee list');
	 }

     public function index(Request $request){
        $user_id = \Request::get('user_id');
        $type = \Request::get('type');
        $search = $request->search? $request->search : '';
        $working_status = $request->working_status? $request->working_status : '';
        $today = Carbon::now()->format('Y-m-d');
        $employee = Employee::where('id','!=',0);
        if($working_status == 'alumni') {
            $employee = $employee->where('last_working_day', '<' , $today);
            if($search != null) {
                $employee = $employee->where(function($query)use($today, $search) {
                            $query->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('skills', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%")
                            ->orWhere('designation', 'LIKE', "%{$search}%")
                            ->where('last_working_day', '<', $today);
                        });
            }
        } else {
            $employee = $employee->where('last_working_day', '>=' , $today)
            ->orWhere('last_working_day', null);
            if($search != null) {
                $employee = $employee->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('skills', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%")
                            ->where(function($query)use($today) {
                            $query->where('last_working_day', '>=', $today)
                            ->orWhere('last_working_day', null);
                        }
                    );
            }
        }
        if($request->pagevalue){
            $employee = $employee->paginate($request->pagevalue);
        }else{
            $employee = $employee->paginate(100);
        }
        // if login client's record exists in projects record
        $id = $user_id;
        if($type == 'client') {
            $project = Project::where('client_id', $id)->get();
            $employee1 = [];
            $test = [];
            foreach($project as $data){
                if(!empty($data->assign_employee))
                {
                    $exassign = array_map('intval', explode(',', $data->assign_employee));
                    foreach($exassign as $key=>$v){
                        if (!in_array($v, $test)){
                            $test[] = $v;
                        }

                    }
                 }
            }
            $employeelist = Employee::whereIn('id', $test)->get();
            if ($employeelist->isNotEmpty()) {
                $employee2 = $employeelist;
            }
        }else{
            $employee2 = $employee;
        }


        return $this->sendResponse($employee2,'Employee list');
     }

     public function calidigteam(Request $request){
        $search = $request->search;
        $pagevalue = $request->pagevalue;
        $today = Carbon::now()->format('Y-m-d');
        $employee = Employee::where('id','!=',0);
        $employee = $employee->where('last_working_day', '>=' , $today)
        ->orWhere('last_working_day', null);
        if($search != null) {
            $employee = $employee->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('skills', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->where(function($query)use($today) {
                        $query->where('last_working_day', '>=', $today)
                        ->orWhere('last_working_day', null);
                    }
                );
        }
        if(!empty($pagevalue)){
            $employee = $employee->paginate($pagevalue);
        }else{
            $employee = $employee->paginate(20);
        }
        return $this->sendResponse($employee,'Employee list');

    }

     public function skills(){
         $skills = Skill::select('id','name')->where('name','!=','')->get();
        return $this->sendResponse($skills,'skills');
     }

     public function skillsfilter(request $request)
     {
         $skill = $request->skills;

         $skills = Employee::where('id','!=',0);
         if($skill != "" && $skill != 'undefined')
         {
             $skills = $skills->where('skills', 'LIKE', "%{$skill}%") ;
         }
        $skills = $skills->paginate(20);
        $date = date('Y-m-d');
        $id = [];
         foreach($skills as $sk){
            $id[] = $sk->id;
         }
         /* Remove Alumni Employees */
         
         
         $project = Employee::whereIn('id', $id)->whereNull('last_working_day')->get();

            foreach($project as $value){
                $empprojects = EmployeeAvailable::where('employee_id',$value->id)->get();
                $total = 0;
				$allocation = [];
                $test = '';
                $projects = [];
                $start_date = [];
                // $end_date = [];

				foreach($empprojects as $emp){
					$empdate = date('Y-m-d', strtotime($emp->created_at));
                    array_push($allocation,$emp->allocation);
                    $allocc = $emp->allocation;
					if($empdate <= $date){
						$test = 'false';
					}else{
						$test = 'true';
                    }

                    $projectname = Project::where('id',$emp->project_id)->get();
                    foreach($projectname as $project){
                        $projects[] = $project->project_name;
                        $projectss = $project->project_name;
                        $s_date = $project->start_date;
                        $e_date = $project->end_date;
                        $start_date[] = array('project_name'=>$projectss,'start_date'=>$s_date,'end_date'=>$e_date,'allocation'=>$allocc);
                        // $end_date[] = $project->end_date;
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

                // print_r($projects);
				$items[] = ['employee_id'=>$value->id,'availability'=>$value->availability,'name'=>$value->first_name.' '.$value->last_name,'project_name'=>$projects,'start_date'=>$start_date,'skills'=>$value->skills,'alloc'=>$allocation];
            }
        return $this->sendResponse($items,'skills');
     }

    public function teamlist(){
		$user_id = \Request::get('user_id');
		$type = \Request::get('type');

		$permission = "";
		$managerinfo = Employee::select('employees.*','employee_roles.role','permissions.permission')->Join('employee_roles','employee_roles.id','employees.role')->Join('permissions','permissions.user_id','employee_roles.id')->where('employees.id',$user_id)->whereRaw('FIND_IN_SET(1,permissions.permission)')
		->first();

		$response = Employee::select('employees.*','employee_roles.role')->leftJoin('employee_roles','employee_roles.id','employees.role');

        if($type == 'employee'){
			if(!empty($managerinfo)){
				$response->where('manager_id',$user_id);
			}
		}

		$response = $response->paginate(20);
		if ($response->isEmpty()) {
			$response = Employee::select('employees.*','employee_roles.role')->leftJoin('employee_roles','employee_roles.id','employees.role')->paginate(20);
		}
		return $this->sendResponse($response,'Team list');
    }

    public function prediction(Request $request){
        $today = Carbon::now()->format('Y-m-d');
        $date = date('m');
        $search = $request->search;
        $pagevalue = $request->pagevalue;
        $project = Employee::where('id','!=',0)->where('last_working_day', '>=' , $today)
        ->orWhere('last_working_day', null);
        if(!empty($search)){
            $project = $project->where('first_name', 'LIKE', "%{$search}%")
									->orWhere('last_name', 'LIKE', "%{$search}%");
        }
        if(!empty($pagevalue)){
            $project = $project->paginate($pagevalue);
        }else{
            $project = $project->paginate(20);
        }
			$items = [];
            foreach($project as $value){
                $empprojects = EmployeeAvailable::where('employee_id',$value->id)->whereRaw('MONTH(created_at) = ?',[$date])->get();
                $total = 0;
				$allocation = [];
                $test = '';
                $projects = [];
                $start_date = [];
                // $end_date = [];

				foreach($empprojects as $emp){
					$empdate = date('m', strtotime($emp->created_at));
                    array_push($allocation,$emp->allocation);
                    $allocc = $emp->allocation;
					if($empdate == $date){
						$test = 'false';
					}else{
						$test = 'true';
                    }

                    $projectname = Project::where('id',$emp->project_id)->get();
                    foreach($projectname as $project){
                        $projects[] = $project->project_name;
                        $projectss = $project->project_name;
                        $s_date = $project->start_date;
                        $e_date = $project->end_date;
                        $start_date[] = array('project_name'=>$projectss,'start_date'=>$s_date,'end_date'=>$e_date,'allocation'=>$allocc);
                        // $end_date[] = $project->end_date;
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

                // print_r($projects);
				$items[] = ['employee_id'=>$value->id,'availability'=>$value->availability,'name'=>$value->first_name.' '.$value->last_name,'project_name'=>$projects,'start_date'=>$start_date,'skills'=>$value->skills,'alloc'=>$total];
            }
        return $this->sendResponse($items,'prediction');

    }

    public function projectchart()
    {
        $e_date = Carbon::now()->addMonths(3)->format('Y-m-d');
        $s_date = Carbon::now()->format('Y-m-d');
        $project = Employee::where('id','!=',0)->get();
			$items = [];
            foreach($project as $value){
                $empprojects = EmployeeAvailable::where('employee_id',$value->id)->whereBetween('created_at', [$s_date, $e_date])->get();
                $total = 0;
				$allocation = [];
                $test = '';
                $projects = [];
                $start_date = [];
                $employee = '';

				foreach($empprojects as $emp){
					$empdate = date('m', strtotime($emp->created_at));
                    array_push($allocation,$emp->allocation);
                    $allocc = $emp->allocation;
                    $employee = $emp->employee_id;
					if(!empty($emp->id)){
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
                $items[] = ['employee_id'=>$employee,'allocc'=>$total,'availability'=>$value->availability,'name'=>$value->first_name.' '.$value->last_name];

            }
        return $this->sendResponse($items,'projectchart');
    }

    public function addemployee(request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'telephone' => 'required',
            'designation' => 'required',
            'gender' => 'required',
          ]);

          if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $file = $request->file;

	$safeName = "";
	if(!empty($file)){
		list($type, $file) = explode(';', $file);
		list(, $file)      = explode(',', $file);
		$file = base64_decode($file);
		$safeName = 'employee_'.time().'.'.'jpeg';
		file_put_contents(public_path().'/employee/'.$safeName, $file);
    }

        $check = Employee::where('email','=',$request->email)->first();
        if(!empty($check))
        {
        return $this->sendResponse('exist','employee is already exist');
        }

        $employee = new Employee;
        if($request->first_name){
            $employee->first_name = $request->first_name;
        }
        if($request->last_name){
            $employee->last_name = $request->last_name;
        }
        if($request->email){
            $employee->email = $request->email;
        }
        if($request->telephone){
            $employee->telephone = $request->telephone;
        }
        if($request->designation){
            $employee->designation = $request->designation;
        }
        if($request->gender){
            $employee->gender = $request->gender;
        }
        if($request->birth_date){
            $employee->dob = $request->birth_date;
        }
        if($request->joining_date){
            $employee->join_date = $request->joining_date;
        }
        if($request->address){
            $employee->address = $request->address;
        }
		if($request->role){
            $employee->role = $request->role;
        }
		if($request->streams){
            $employee->streams = $request->streams;
        }
        if($request->skills){
            // $employee->skills = implode(",",$request->skills);
            $string=[];
            foreach ($request->skills as $value){
                $string[] = $value['name'];

                $exist = Skill::where('name',$value['name'])->first();
                if(empty($exist))
                {
                    $skill = new Skill;
                    $skill->name = $value['name'];
                    $skill->save();
                }
            }
            $stringto = implode(",",$string);
            $employee->skills = $stringto;

        }
        if($safeName){
            $employee->image = $safeName;
        }
        if($request->manager_id){
            $employee->manager_id = $request->manager_id;
        }
        if($request->last_working_day){
            $employee->last_working_day = $request->last_working_day;
        }
		$password = $this->randomPassword();
        $employee->password = $password;
        $employee->status = 1;
        $employee->save();


        $data['subject'] = 'Employee Account Registration';
        $data['name'] = $request->first_name.' '.$request->last_name;
        $data['email'] = $request->email;
        $data['password'] = $password;
        $data['type'] = 'Employee';
        $data['url'] = env('SITE_URL');

        if (strpos($data['email'], 'calidig.com') !== false) {
            $data['email'] = $data['email'].'.test-google-a.com';
        }

        Mail::send('email/employee-account', $data, function($message) use ($data) {
          $message->to($data['email'])
          ->subject($data['subject']);
			$message->from('noreply@calidig.com','No Reply');
        });

        return $this->sendResponse('','Employee Create successfully');


    }

	public function randomPassword() {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array();
		$alphaLength = strlen($alphabet) - 1;
		for ($i = 0; $i < 12; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass);
	}

    public function edit($id)
    {
        $employee = Employee::find($id);

        if (!empty($employee)) {
			// $exassign = explode(",",$project->assign_employee);
			$exassign =  explode(',', $employee->skills);
			$string=[];
            foreach ($exassign as $value){
                $string[] = ['id'=>$employee->id,'name'=>$value];
            }
			// $employee = Employee::select('id',DB::raw('CONCAT(first_name,\' \',last_name) AS name'))->whereIn('id', $exassign)->get();
			if (!empty($exassign)) {
                $employee['name'] = $string;
			}else{
				$employee->skills = "";
            }

            return response()->json($employee);

        }

        if (is_null($employee)) {
            return $this->sendError('employee not found.');
        }
    }

    public function update(Request $request, $id)
    {

        $input = $request->all();

        $validator = Validator::make($input, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'telephone' => 'required',
            'designation' => 'required',
            'gender' => 'required',
        ]);


        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $file = $request->file;
		$safeName = "";
		if(!empty($file)){
			list($type, $file) = explode(';', $file);
			list(, $file)      = explode(',', $file);
			$file = base64_decode($file);
			$safeName = 'employee_'.time().'.'.'jpeg';
			file_put_contents(public_path().'/employee/'.$safeName, $file);
		}


        $employee = Employee::find($id);

        if($request->first_name){
            $employee->first_name = $request->first_name;
        }
        if($request->last_name){
            $employee->last_name = $request->last_name;
        }
        if($request->email){
            $employee->email = $request->email;
        }
        if($request->telephone){
            $employee->telephone = $request->telephone;
        }
        if($request->designation){
            $employee->designation = $request->designation;
        }
        if($request->gender){
            $employee->gender = $request->gender;
        }
        if($request->birth_date){
            $employee->dob = $request->birth_date;
        }
        if($request->joining_date){
            $employee->join_date = $request->joining_date;
        }
        if($request->address){
            $employee->address = $request->address;
        }
		if($request->role){
            $employee->role = $request->role;
        }
		if($request->streams){
            $employee->streams = $request->streams;
        }
        if($request->skills){
            // $employee->skills = implode(",",$request->skills);
            $string=[];
            foreach ($request->skills as $value){
                $string[] = $value['name'];

                $exist = Skill::where('name',$value['name'])->first();
                if(empty($exist))
                {
                    $skill = new Skill;
                    $skill->name = $value['name'];
                    $skill->save();
                }
            }
            $stringto = implode(",",$string);
            $employee->skills = $stringto;

        }
        if($safeName){
            $employee->image = $safeName;
        }
		if($request->manager_id){
            $employee->manager_id = $request->manager_id;
        }
        
        
        
        /* Validation DOE should always be greater than DOJ */
        
        
		if($request->joining_date < $request->last_working_day){
            $employee->last_working_day = $request->last_working_day;
        }

        $employee->save();

        return response()->json($employee);
    }

    public function destroy($id)
    {
        $employee = Employee::find($id);
        $employee->delete();

        return response()->json($employee);
    }

	public function userteamlist(Request $request){
        $today = Carbon::now()->format('Y-m-d');
		$user_id = \Request::get('user_id');
		$type = \Request::get('type');
        $search = $request->search;
		$permission = "";
		$managerinfo = Employee::select('employees.*','employee_roles.role','permissions.permission')->Join('employee_roles','employee_roles.id','employees.role')->Join('permissions','permissions.user_id','employee_roles.id')->where('employees.id',$user_id)->whereRaw('FIND_IN_SET(4,permissions.permission)')->where('employees.last_working_day', '>=' , $today)
        ->orWhere('employees.last_working_day', null)
		->first();

		$response = Employee::select('employees.*','manager.first_name as mfirst','manager.last_name as mlast','employee_roles.role as userrole')->Join('employee_roles','employee_roles.id','employees.role')->leftJoin('employees as manager','manager.id','employees.manager_id')->where('employees.last_working_day', '>=' , $today)
        ->orWhere('employees.last_working_day', null);

        if($type == 'employee'){
			if(!empty($managerinfo)){
				$response->where('employees.manager_id',$user_id);
			}
		}
        if(!empty($search)){
            $response = $response->where('employees.first_name', 'LIKE', "%{$search}%")
                                ->orWhere('employees.last_name', 'LIKE', "%{$search}%")
                                ->orWhere('employees.telephone', 'LIKE', "%{$search}%");
        }
		$response = $response->paginate(20);
		if ($response->isEmpty()) {
			$response = Employee::select('employees.*','manager.first_name as mfirst','manager.last_name as mlast','employee_roles.role as userrole')->Join('employee_roles','employee_roles.id','employees.role')->leftJoin('employees as manager','manager.id','employees.manager_id')->where('employees.last_working_day', '>=' , $today)
            ->orWhere('employees.last_working_day', null)->paginate(20);
		}
		return $this->sendResponse($response,'Team list');
    }

    public function admin($id)
    {
        $admin = User::find($id);
        return response()->json($admin);
    }

    public function profileupdate(Request $request, $id)
    {
        $file = $request->file;
        $type = $request->type;
		$safeName = "";


        if($type=='employee')
        {   if(!empty($file)){
			list($type, $file) = explode(';', $file);
			list(, $file)      = explode(',', $file);
			$file = base64_decode($file);
			$safeName = 'employee_'.time().'.'.'jpeg';
			file_put_contents(public_path().'/employee/'.$safeName, $file);
            }
            $employee = Employee::find($id);
            if($safeName){
                $employee->image = $safeName;
            }
            $employee->save();
            return response()->json($employee);
        }elseif($type=='client'){
            if(!empty($file)){
                list($type, $file) = explode(';', $file);
                list(, $file)      = explode(',', $file);
                $file = base64_decode($file);
                $safeName = 'employee_'.time().'.'.'jpeg';
                file_put_contents(public_path().'/client/'.$safeName, $file);
            }
            $client = Client::find($id);
            if($safeName){
                $client->image = $safeName;
            }
            $client->save();
            return response()->json($client);
        }else{
            if(!empty($file)){
                list($type, $file) = explode(';', $file);
                list(, $file)      = explode(',', $file);
                $file = base64_decode($file);
                $safeName = 'employee_'.time().'.'.'jpeg';
                file_put_contents(public_path().'/user/'.$safeName, $file);
            }
            $user = User::find($id);
            if($safeName){
                $user->image = $safeName;
            }
            $user->save();
            return response()->json($user);
        }
    }
}
