<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
Use \Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Validator;
use Illuminate\Support\Str;
use Mail;
use PDF;

class DailyReportController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */


    public function index(request $request){
        $user_id = \Request::get('user_id');;
		if($user_id == 'undefined'){
			$user_id = '';
		}
		//,'employees.first_name'
        $dsr = DailyReport::select('daily_reports.*','projects.project_name','employees.first_name')
        ->join('projects','daily_reports.project_id','projects.id')
        ->join('employees','daily_reports.user_id','employees.id');
        if($user_id != '' && $user_id != 'undefined'){
            $dsr = $dsr->where('daily_reports.user_id',$user_id);
        }
        $dsr = $dsr->paginate(20);

		foreach($dsr as $res){
            if($user_id != '' && $user_id != 'undefined'){
                $res->links = env('APP_URL').'dsrpdf?project=&start_date=&end_date=&employee='.$user_id;
                $res->excel = env('APP_URL').'fileexport?project=&start_date=&end_date=&employee='.$user_id;
            }else{
                $res->links = env('APP_URL').'dsrpdf?project=&start_date=&end_date=&employee=';
                $res->excel = env('APP_URL').'fileexport?project=&start_date=&end_date=&employee=';
            }
			$descriptions = trim($res->description);
			$exdesc = explode('---',$descriptions);
			$exhour = explode('---',$res->hour);
			$i = 0;
			$description = $hour = '';
			foreach($exdesc as $exdes){
				$exdes = trim($exdes);
				if(!empty($exdes)){
					$description .= "&#8226; ".strip_tags($exdes)."<br>";
				}
				$hour .=  "&#8226; ".$exhour[$i]."<br>";
				$i++;
			}

			$res->description = $description;
			$res->hour = $hour;
		}
       return $this->sendResponse($dsr,'Daily report list');
    }

    public function dsrfilter(request $request){
        $project_id = $request->project;
        $type = \Request::get('type');
        $emp_id = '';
        if($request->employee != '')
        {
            $emp_id = $request->employee;
        }
        if($type == 'employee')
        {
            $emp_id = $request->user_id;
        }
        if($type == 'client'){
            $user_id = $request->user_id;
        }
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $dsr = DailyReport::select('daily_reports.*','projects.project_name','projects.client_id','employees.first_name','employees.last_name')
        ->join('projects','daily_reports.project_id','projects.id')
        ->join('employees','daily_reports.user_id','employees.id')
        ->orderBy('created_at', 'desc');


        if($type == 'client'){
            $dsr1 = $dsr->get();
           foreach ($dsr1 as $data) {
               if($data->client_id == $user_id) {
                   $dsr = $dsr->where('projects.client_id',$data->client_id);
                   $dsr = $dsr->where('projects.project_name','!=','Calidig-Internal');
                   $dsr = $dsr->where('daily_reports.is_approved', 'Yes');
               }
           }
       } else {
           $dsr = $dsr;
       }

        if($project_id != '' && $project_id != 'undefined' && $project_id != 'all'){
            $dsr = $dsr->where('daily_reports.project_id',$project_id);
        }
        if($emp_id != '' && $emp_id != 'undefined' && $emp_id != 'all'){
            $dsr = $dsr->where('daily_reports.user_id',$emp_id);
        }
        if($start_date != '' && $start_date != 'undefined'){
            $dsr = $dsr->whereBetween('daily_reports.start_date', [$start_date, $end_date]);
        }
        $dsr = $dsr->paginate(20);

                // calculate total dsr hours
                $dsr1 = $dsr->map(function($data){
                    $hours = explode("--",$data->hour);
                    if(count($hours) > 1) {
                        $totalHours = 0 ;
                        for($i=0; $i<count($hours); $i++) {
                            $totalHours += abs($hours[$i]);
                        }
                    }
                    else {
                        if( empty($data->hour) ) {
                            $hours = 0;
                         }
                         $totalHours = $hours;
                    }
                    $data->totalHours = $totalHours;
                    return $data;
                });
                foreach($dsr as $d) {
                    foreach($dsr1 as $dd) {
                        if($d-> id == $dd->id) {
                            $d->totalHours = $dd->totalHours;
                        }
                    }
                }

        foreach($dsr as $link){
            // $pro = $link->project_id;
            $link->links = env('APP_URL').'dsrpdf?project='.$project_id.'&start_date='.$start_date.'&end_date='.$end_date.'&employee='.$emp_id;
            $link->excel = env('APP_URL').'fileexport?project='.$project_id.'&start_date='.$start_date.'&end_date='.$end_date.'&employee='.$emp_id;
            $descriptions = trim($link->description);
            $exdesc = explode('---',$descriptions);
			$exhour = explode('---',$link->hour);
			$i = 0;
			$description = $hour = '';
			foreach($exdesc as $exdes){

				if(!empty($exdes)){
					$description .= strip_tags($exdes)."<br>";
				}

				$hour .= $exhour[$i]."<br>";
				$i++;
			}

			$link->description = $description;
			$link->hour = $hour;
        }
       return $this->sendResponse($dsr,'Daily report list');
    }

    public function project_select(request $request)
    {
        $user_id = $request->user_id;
        $project = Project::where('id','!=',0);
        if($user_id != '' && $user_id != 'undefined')
        {
            $project = $project->whereRaw('FIND_IN_SET(?,assign_employee)', [$user_id]);
        }
        $project = $project->get();
       return $this->sendResponse($project,'project list');

    }

    // list projects

    public function listProjects() {
        $user_id = \Request::get('user_id');
	    $type = \Request::get('type');
        // super user
        if($type == 'user') {
            $project = Project::get();
        }
        // employees only
        if($type == 'employee') {
            $a = Project::whereRaw('FIND_IN_SET(?,assign_employee)', [$user_id]);
            $project = Project::where('project_name', '=', 'Calidig-Internal')
			->union($a)
			->get();
        }
        // clients only
        if($type == 'client') {
            $project = Project::where('client_id', $user_id)->where('project_name', '!=', 'Calidig-Internal')->get();
        }
       return $this->sendResponse($project,'project list');
    }

    public function add_dsr(request $request){

       $validator = Validator::make($request->all(), [

           'user_id' => 'required',
           'project' => 'required',
           //'description' => 'required',
           'start_date' => 'required',
           //'hour' => 'required',
         ]);

         if($validator->fails()){
           return $this->sendError('Validation Error.', $validator->errors());
       }

       $user_id = \Request::get('user_id');

       $DSR_details = DailyReport::where('user_id',$user_id)
                        ->whereDate('start_date', $request->start_date)
                        ->where('project_id', $request->project)
                        ->first();
        if(!empty($DSR_details)) {
            return $this->sendResponse(['details exist' => true], 'DSR has already been filled');
        } else {
            $emp = Employee::where('id',$user_id)->first();
            $firstname = $emp->first_name;
            $lastname = $emp->last_name;
            $empemail = $emp->email;
             $descriptions = $hours = [];
             if(!empty($request->items)){
                 foreach($request->items  as $item){
                     $descriptions[] = $item['description'];
                     $hours[] = $item['hour'];
                 }
             }

             $totalHours = 0;
             $isHalfDay = false;
            for($i=0; $i<count($hours); $i++) {
                $totalHours += $hours[$i];
            }
            $leaveDetails = $this->checkTodaysLeave($request->start_date, $user_id);
            if ($leaveDetails['leaveDuration'] == 'halfday') {
                $isHalfDay = true;
            }

            // chcek is employee is on half day
            if($isHalfDay){
                // restrict user if entered working hours are less than 4
                if($totalHours < 4) {
                    return $this->sendResponse(['total hours' => $totalHours], 'Total hours must be >= 4');
                }
            }
            else  {
                // restrict user if entered working hours are less than 9
                if($totalHours < 9) {
                    return $this->sendResponse(['total hours' => $totalHours], 'Total hours must be >= 9');
                }else {
                    if($totalHours > 15 ) {
                        return $this->sendResponse(['total hours' => $totalHours], 'Total hours can be <= 15');
                    }
                    else {
                        $totalHours = $totalHours;
                    }
                }
            }

            $dsr = new DailyReport;

            $dsr->user_id = $user_id;
            $dsr->project_id = $request->project;
            $dsr->description = implode("---",$descriptions);
            $dsr->start_date = $request->start_date;
            $dsr->hour = implode("---",$hours);
            $dsr->is_approved = 'No';
            $dsr->save();

            $date = Carbon::now();
            $date->format('Y-m-d');

            $attendance = new Attendance;
            $attendance->employee_id = \Request::get('user_id');;
            $attendance->date = $date;

            // mark half day attendance for employee => status = 2
            if($totalHours >= 4 && $totalHours <9) {
             $attendance->status = 2;
            }
             // mark full day attendance for employee => status = 1
             if($totalHours >= 9) {
                $attendance->status = 1;
               }

            $attendance->save();

         //    $project = Project::where('id',$request->project)->first();
         //    $email = $project->client_email;
         //    $project_name = $project->project_name;

         //    $data = [
         //     'subject' => 'Daily Report:'.$project_name,
         //     'name' => $firstname. ' ' .$lastname,
         //     'empemail' => $empemail,
         //     'email' => $email,
         //     'date' => $request->start_date,
         //     'url' => env('SITE_URL')
         //   ];

         //   if (strpos($data['email'], 'calidig.com') !== false) {
         //     $data['email'] = $data['email'].'.test-google-a.com';
         // }

         //   Mail::send('email/dsr', $data, function($message) use ($data) {
         //     $message->to($data['email'])
         //     ->subject($data['subject']);
         // 		$message->from('noreply@calidig.com','No Reply');
         //   });
         return response()->json($dsr);

        }

   }
   public function destroy($id)
   {
       $dsr = DailyReport::find($id);
       $dsr->delete();

       return response()->json($dsr);

   }
   public function edit($id)
    {
        $dsr = DailyReport::find($id);
        $user_id = \Request::get('user_id');;
        $employee = Employee::where('id',$user_id)->first();
		$items = [];
		if(!empty($dsr->description)) {
			$exdesc = explode('---',$dsr->description);
			$exhour = explode('---',$dsr->hour);
			$i = 0;
			foreach($exdesc as $exdes){
				$items[] = array('description'=>$exdes,'hour'=>$exhour[$i]);
				$i++;
			}
		}
        $dsr->items = $items;
        $dsr->emp_name = $employee->first_name. ' ' .$employee->last_name;
        if (is_null($dsr)) {
            return $this->sendError('Daily report not found.');
        }
        return response()->json($dsr);
    }

    public function update(Request $request, $id)
    {

        $input = $request->all();

        $validator = Validator::make($input, [
            'project' => 'required',
            //'description' => 'required',
            'start_date' => 'required',
            //'hour' => 'required',
        ]);


        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $DSR_details = DailyReport::where('user_id',$request->user_id)
                        ->whereDate('start_date', $request->start_date)
                        ->where('project_id', $request->project)
                        ->first();
        if(!empty($DSR_details)) {
            return $this->sendResponse(['details exist' => true], 'DSR has already been filled');
        } else {

		$descriptions = $hours = [];
		if(!empty($request->items)){
			foreach($request->items  as $item){
				$descriptions[] = $item['description'];
				$hours[] = $item['hour'];
			}
		}

        $totalHours = 0;
        $isHalfDay = false;
        for($i=0; $i<count($hours); $i++) {
            $totalHours += $hours[$i];
        }
        $leaveDetails = $this->checkTodaysLeave($request->start_date, $request->user_id);
        if ($leaveDetails['leaveDuration'] == 'halfday') {
            $isHalfDay = true;
        }

        // chcek is employee is on half day
        if($isHalfDay){
            // restrict user if entered working hours are less than 4
            if($totalHours < 4) {
                return $this->sendResponse(['total hours' => $totalHours], 'Total hours must be >= 4');
            }
        }
        else  {
            // restrict user if entered working hours are less than 9
            if($totalHours < 9) {
                return $this->sendResponse(['total hours' => $totalHours], 'Total hours must be >= 9');
            }else {
                if($totalHours > 15 ) {
                    return $this->sendResponse(['total hours' => $totalHours], 'Total hours can be <= 15');
                }
                else {
                    $totalHours = $totalHours;
                }
            }
        }

        $dsr = DailyReport::find($id);
        $dsr->project_id = $request->project;
        $dsr->description = implode("---",$descriptions);
        $dsr->start_date = $request->start_date;
        $dsr->hour = implode("---",$hours);
        $dsr->save();

        return response()->json($dsr);
        }
    }

    // approve/reject DSR by admin

    public function isDSRapprovedByAdmin(Request $request) {
        $id = $request->id;
        $id1 = is_array($id);
        if($id1 == false){
            $dsr = DailyReport::find($id);
            $dsr1 = $dsr->is_approved = 'Yes';
            $dsr1 = $dsr->save();
        }else{
            $dsr = DailyReport::whereIn('id',$id)->get();
            foreach($dsr as $dsr1){
               $dsr1->is_approved = 'Yes';
                $dsr1->save();
            }
        }
        return $this->sendResponse($dsr1, 'DSR has been approved by admin.');
    }

    // list loggedin user respective DSR

    public function listDSR(){
        $user_id = \Request::get('user_id');
        $type = \Request::get('type');
        if($type=='user'){
            $user_id = '';
        }
        $dsr = DailyReport::select('daily_reports.*','projects.project_name','projects.client_id','employees.first_name','employees.last_name')
        ->join('projects','daily_reports.project_id','projects.id')
        ->join('employees','daily_reports.user_id','employees.id')
        ->orderBy('created_at', 'desc');

            if($type == 'client'){
                 $dsr1 = $dsr->get();
                foreach ($dsr1 as $data) {
                    if($data->client_id == $user_id) {
                        // print_r($data->client_id);
                        $dsr = $dsr->where('projects.client_id',$data->client_id);
                        $dsr = $dsr->where('projects.project_name','!=','Calidig-Internal');
                        $dsr = $dsr->where('daily_reports.is_approved', 'Yes');
                    }
                }
            }elseif($type == 'employee')
            {
                    $dsr = $dsr->where('daily_reports.user_id',$user_id);
            }
             else {
                $dsr = $dsr;
            }

            $dsr = $dsr->orderBy('created_at', 'desc')->paginate(20);


        // calculate total dsr hours
        $dsr1 = $dsr->map(function($data){
            $hours = explode("--",$data->hour);
            if(count($hours) > 1) {
                $totalHours = 0 ;
                for($i=0; $i<count($hours); $i++) {
                    $totalHours += abs($hours[$i]);
                }
            }
            else {
                if( empty($data->hour) ) {
                    $hours = 0;
                 }
                 $totalHours = $hours;
            }
            $data->totalHours = $totalHours;
            return $data;
        });
        foreach($dsr as $d) {
            foreach($dsr1 as $dd) {
                if($d-> id == $dd->id) {
                    $d->totalHours = $dd->totalHours;
                }
            }
        }

		foreach($dsr as $res){
            if($user_id != '' && $user_id != 'undefined'){
                $res->links = env('APP_URL').'dsrpdf?project=&start_date=&end_date=&employee='.$user_id.'&type='.$type;
                $res->excel = env('APP_URL').'fileexport?project=&start_date=&end_date=&employee='.$user_id.'&type='.$type;
            }else{
                $res->links = env('APP_URL').'dsrpdf?project=&start_date=&end_date=&employee=';
                $res->excel = env('APP_URL').'fileexport?project=&start_date=&end_date=&employee=';
            }
			$descriptions = trim($res->description);
			$exdesc = explode('---',$descriptions);
			$exhour = explode('---',$res->hour);
			$i = 0;
			$description = $hour = '';
			foreach($exdesc as $exdes){
				$exdes = trim($exdes);
				if(!empty($exdes)){
					// $description .= "&#8226; ".strip_tags($exdes)."<br>";
					$description .= strip_tags($exdes)."<br>";
				}
				$hour .=  $exhour[$i]."<br>";
				$i++;
			}

			$res->description = $description;
			$res->hour = $hour;
        }

        foreach($dsr as $dsr2)
        {
            $dsr2->isSelected = false;
        }
       return $this->sendResponse($dsr,'Daily report list');
    }

    public function checkTodaysLeave($date, $uid) {
        $leaveData = Leave::where('employee_id', $uid)
        ->where('start_date', $date)
        ->orwhere('end_date', $date)
        ->get();

        $isOnleave = false;
        $leaveDuration = '';
        if(!empty($leaveData)) {
            foreach($leaveData as $leave){
                if($leave->day == 'halfday') {
                    $isOnleave = false;
                    $leaveDuration = 'halfday';
                } else {
                    if($leave->status == 'A' || $leave->status == 'AU') {
                        $isOnleave = true;
                        $leaveDuration = $leave->day;
                    }
                    else {
                        $isOnleave = false;
                        $leaveDuration = $leave->day;
                    }
                }
            }
        }
        $reponse = ['employeeOnLeave' => $isOnleave, 'leaveDuration' => $leaveDuration];
        return $reponse;
   }


}
