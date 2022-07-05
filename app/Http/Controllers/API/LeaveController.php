<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\DailyReport;
use App\Models\Leave;
use App\Models\Notification;
use App\Models\LeaveBalance;
use App\Models\Guideline;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;
use DB;
use Mail;
use Carbon\Carbon;

class LeaveController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */


    public function index(Request $request){

		$today = $request->today;
        $leave = Leave::select('leaves.*','employees.first_name','employees.last_name')
		->join('employees','leaves.employee_id','employees.id');

		if($today == 'today') {
			$leave->where('start_date', '<=', date('Y-m-d'))
				->where('end_date', '>=', date('Y-m-d'));
        }
        if($request->pagevalue){
		    $leave = $leave->orderBy('leaves.id', 'DESC')->paginate($request->pagevalue);
        }else{
            $leave = $leave->orderBy('leaves.id', 'DESC')->paginate(10);
        }
		$year = date('Y');
		$month = date('m');

		if($month <= 03){
			$year2 = $year-1;
		}else{
			$year2 = $year;
		}
		$i =0;
		foreach($leave as $le) {
			//if($i == 0) {
			$le->balance = LeaveBalance::where('user_id',$le->employee_id)->where('leave_balances.year',$year2)->sum('entitled');


			$res = Leave::where('employee_id',$le->employee_id)
			->where('start_date', '>=', $year2.'-04-01')
			->where('start_date', '<=', date('Y-m-d'))
			->where('status','A')
			->get();

			//usedleave = 0;

			foreach($res as $ress) {
				$le->usedleave += $this->getleavecounts($ress->start_date,$ress->end_date,$ress->day);
			}
			///$le->usedleave =

            $le->apply = Leave::where('status','I')->count();
            $le->weakleave = Leave::whereBetween('start_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
			$le->monthleave = Leave::whereMonth('start_date', Carbon::now()->month)->count();

            $le->leavecount = $this->getleavecounts($le->start_date,$le->end_date,$le->day);

            $le->todayleavecount = Leave::where('start_date', '<=', Carbon::now())
				->where('end_date', '>=', Carbon::now())
				->count();

			//}
			$UsedLeaveBalance = Leave::where('employee_id',$le->employee_id)->where('start_date', '>=', $year2.'04-01')
			->where('start_date', '<=', date('Y-m-d'))->where(function ($query) {
			$query->where('status','A')
				  ->orWhere('status','AU');
			})->get();
            $le->UsedBalancecount = 0;
            foreach($UsedLeaveBalance as $leav){
                $le->UsedBalancecount += $this->getleavecounts($leav->start_date,$leav->end_date,$leav->day);
            }
            $le->remaining = $le->entitled - $le->UsedBalancecount;
				$i++;
		}


		$apply = Leave::where('status','I')->count();
		$weakleave = Leave::whereBetween('start_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
		$monthleave = Leave::whereMonth('start_date', Carbon::now()->month)->count();
		$todayleavecount = Leave::where('start_date', '<=', date('Y-m-d'))
				->where('end_date', '>=', date('Y-m-d'))
				->count();
		$allleave = Leave::select('leaves.*','employees.first_name','employees.last_name')
		->join('employees','leaves.employee_id','employees.id')->count();
		$response = array('leave'=>$leave, 'apply'=>$apply,'monthleave'=>$monthleave,'weakleave'=>$weakleave,'todayleavecount'=>$todayleavecount,'allleave'=>$allleave );
       return $this->sendResponse($response,'Leave list');
    }


    public function employeeleaves($id){
        $id = \Request::get('user_id');
        $leave = Leave::where('employee_id','=',$id)->orderBy('id', 'DESC')->paginate(20);
		$year = date('Y');
		$month = date('m');

		if($month <= 03){
			$year2 = $year-1;
		}else{
			$year2 = $year;
		}
        foreach($leave as $leav){

			$leav->balance = LeaveBalance::where('user_id',$id)->where('leave_balances.year',$year2)->sum('entitled');
            $leav->leavecount = $this->getleavecounts($leav->start_date,$leav->end_date,$leav->day);
        }
       return $this->sendResponse($leave,'Leave list');
    }
    public function addleave(request $request){
       $id = \Request::get('user_id');
       $type = \Request::get('type');
       $emp_data = Employee::where('id', $id)->first();
       $desig = $emp_data->designation;
       $uid = $request->employee_id;

       $validator = Validator::make($request->all(), [
           'employee_id' => 'required',
           'title' => 'required',
           'description' => 'required',
           'start_date' => 'required',
           'end_date' => 'required',
           'leave_type' => 'required',
         ]);

         if($validator->fails()){
           return $this->sendError('Validation Error.', $validator->errors());
       }
       $id = \Request::get('user_id');
       $previousLeaves = Leave::where('start_date', '<=', $request->end_date)
                        ->where('end_date', '>=', $request->start_date)
                        ->orwhere('start_date', $request->start_date)
                        ->orwhere('end_date', $request->end_date)
                        ->get();

        $halfdaysCount = 0;
        $fulldaysCount = 0;
        $otherdaysCount = 0;
        if(!empty($previousLeaves)) {
            foreach($previousLeaves as $l){
                if($l->employee_id == $request->employee_id) {
                    $status = $l->status;
                    if($l->day == 'halfday' ) {
                        $halfdaysCount = $halfdaysCount+1;
                        if($halfdaysCount == 2) {
                            return $this->sendResponse(['halfdaysCount' => $halfdaysCount], 'You have already applied leave for this date');
                        }
                    } else if($l->day == 'fullday'){
                        $fulldaysCount = $fulldaysCount+1;
                        if($fulldaysCount > 0) {
                            return $this->sendResponse(['fulldaysCount' => $fulldaysCount], 'You have already applied leave for this date');
                        }
                    } else {
                        $otherdaysCount = $otherdaysCount+1;
                        if($otherdaysCount > 0) {
                            return $this->sendResponse(['otherdaysCount' => $otherdaysCount], 'You have already applied leave for this date');
                        }
                    }
                }
            }
        }
        if($id == $uid && $desig == 'HR' && $type == 'employee') {
            $request->status = 'I';
        }

		$type = \Request::get('type');
        $leave = new Leave;

           $leave->employee_id = $request->employee_id;
           $leave->title = $request->title;
           $leave->description = $request->description;
           $leave->start_date = $request->start_date;
           $leave->end_date = $request->end_date;
           $leave->leave_type = $request->leave_type;
           if($request->day){
            //    if($halfdaysCount == 1) {
            //      $leave->day = 'halfday';
            //    } else {
                $leave->day = $request->day;
            //    }
           }
           if($request->status){
           $leave->status = $request->status;
           }else{
           $leave->status = 'A';
           }
            $leave->save();
            $status = $leave->status;
            if($request->status == 'I')
            {
                $emp = Employee::where('id',$request->employee_id)->first();
                $firstname = $emp->first_name;
                $lastname = $emp->last_name;
                $employee_email = $emp->email;

                $user = User::find(1);
                $email = $user->email;
                $name = $user->name;

				//$email = 'poonam.kaur@calidig.com';
				if (strpos($email, 'calidig.com') !== false) {
					$email = $email.'.test-google-a.com';
				}
                $emprole = Employee::where('role','1')->first();
                $roleemail = $emprole->email;

				//$roleemail = 'rohit@calidig.com';
				if (strpos($roleemail, 'calidig.com') !== false) {
					$roleemail = $roleemail.'.test-google-a.com';
				}

                $email_array = array($email,$roleemail);
				
				$AdminUsers = User::get();
				foreach($AdminUsers as $AdminUser) {
					$email_array[] = $AdminUser->email;
				}
                $data['subject'] = 'Leave request'. '-' .$firstname.' '.$lastname ;
                $data['name'] = $firstname.' '.$lastname;
                $data['email'] = $email_array;
                $data['employeeemail'] = $employee_email;
                $data['type'] = $request->leave_type;
                $data['title'] = $request->title;
                $data['date'] = $request->start_date. ' to ' .$request->end_date;
                $data['days'] = $this->getleavecounts($request->start_date,$request->end_date,$request->day);
                $data['url'] = env('SITE_URL');


                Mail::send('email/admin-leave', $data, function($message) use ($data) {
                $message->to($data['email'])
                ->subject($data['subject']);
				$message->from('noreply@calidig.com','No Reply');
                });
            }
            if($status == 'A')
            {
                $emp = Employee::where('id',$request->employee_id)->first();
                $firstname = $emp->first_name;
                $lastname = $emp->last_name;
                $employee_email = $emp->email;

                // return $user;
                $data['subject'] = 'Leave Approve';
                $data['name'] = $firstname.$lastname;
                $data['employeeemail'] = $employee_email;
                $data['type'] = $request->leave_type;
                $data['title'] = $request->title;
                $data['date'] = $request->start_date. ' to ' .$request->end_date;
				$data['days'] = $this->getleavecounts($request->start_date,$request->end_date,$request->day);
                $data['url'] = env('SITE_URL');
				if (strpos($data['employeeemail'], 'calidig.com') !== false) {
					$data['employeeemail'] = $data['employeeemail'].'.test-google-a.com';
				}

                Mail::send('email/admin-leave', $data, function($message) use ($data) {
                $message->to($data['employeeemail'])
                ->subject($data['subject']);
				$message->from('noreply@calidig.com','No Reply');
                });
            }

			if($type == 'user'){
			   $title = $leave->title. '<br> Your leave has been approved <br>';
			   $description = $leave->description;
			   $employee_id = $leave->employee_id;

			   $notification = new Notification;
			   $notification->title = $title;
			   $notification->description = $description;
			   $notification->employee_id = $employee_id;
			   $notification->type = 'L';
			   $notification->save();
			}

            if($type == 'employee'){
                $title = $leave->title. '<br> Leave request <br>';
                $description = $leave->description;
                $employee_id = $leave->employee_id;

                $notification = new Notification;
                $notification->title = $title;
                $notification->description = $description;
                $notification->employee_id = $employee_id;
                $notification->type = 'L';
                $notification->save();
             }

       return response()->json($leave);

   }
   public function destroy($id)
   {
       $leave = Leave::find($id);
       $leave->delete();

       return response()->json($leave);

   }
   public function edit($id)
    {
        $leave = Leave::find($id);


        if (is_null($leave)) {
            return $this->sendError('leave not found.');
        }
        return response()->json($leave);
    }

    public function update(Request $request, $id)
    {

        $input = $request->all();
        $emp_id = \Request::get('user_id');
        $validator = Validator::make($input, [
            'employee_id' => 'required',
            'title' => 'required',
            'description' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'leave_type' => 'required',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $previousLeaves = Leave::where('start_date', '<=', $request->end_date)
                        ->where('end_date', '>=', $request->start_date)
                        ->orwhere('start_date', $request->start_date)
                        ->orwhere('end_date', $request->end_date)
                        ->get();

        $halfdaysCount = 0;
        $fulldaysCount = 0;
        $otherdaysCount = 0;
        if(!empty($previousLeaves)) {
        foreach($previousLeaves as $l){
            if($l->employee_id == $request->employee_id && $l->id == $id) {
                if($l->day == 'halfday' ) {
                    $halfdaysCount = $halfdaysCount+1;
                    if($halfdaysCount == 2) {
                        return $this->sendResponse(['halfdaysCount' => $halfdaysCount], 'You have already applied leave for this date');
                    }
                } else if($l->day == 'fullday'){
                    $fulldaysCount = $fulldaysCount+1;
                    if($fulldaysCount > 0) {
                        return $this->sendResponse(['fulldaysCount' => $fulldaysCount], 'You have already applied leave for this date');
                    }
                } else {
                    $otherdaysCount = $otherdaysCount+1;
                    if($otherdaysCount > 0) {
                        
                        $leave = Leave::find($id);
						$leave->employee_id = $request->employee_id;
						$leave->title = $request->title;
						$leave->description = $request->description;
						$leave->start_date = $request->start_date;
						$leave->end_date = $request->end_date;
						$leave->leave_type = $request->leave_type;
						$leave->day = $request->day;
						$leave->save();

						return response()->json($leave);
        
        
                        // return $this->sendResponse(['otherdaysCount' => $otherdaysCount], 'You have already applied leave for this date');
                    }
                }
            }
            }
        }

        $leave = Leave::find($id);

        $leave->employee_id = $request->employee_id;
        $leave->title = $request->title;
        $leave->description = $request->description;
        $leave->start_date = $request->start_date;
        $leave->end_date = $request->end_date;
        $leave->leave_type = $request->leave_type;
        $leave->day = $request->day;
        $leave->save();

        return response()->json($leave);
    }

    public function leavebalance(request $request)
    {
        $year = $request->year;
        $today = Carbon::now()->format('Y-m-d');
        $currentyear = '';
        if(!empty($year)){
			$newYear = $year;
            $leaves = LeaveBalance::select('leave_balances.*')->where('leave_balances.year',$year)
        ->paginate(10);
        $currentyear = date('Y');
        }else{

            $previous_year = date("Y",strtotime("-1 year"));
            $month = date('m');
			$year = date('Y');
			if(!empty($currentyear)) {
				$newYear = $currentyear;
			}else {

				if($month <= 3)
				{
					$newYear = date('Y') -1;
				}else{
					$newYear = date('Y');
				}
			}

			if($month <= 3)
			{
				$currentyear = $year-1;
			}else{
				$currentyear = $year;
			}

            $leaves = LeaveBalance::select('leave_balances.*','employees.first_name','employees.last_name', 'employees.last_working_day')
                    ->join('employees','leave_balances.user_id','employees.id')
                    ->where('employees.last_working_day', '>=' , $today)
                    ->orWhere('employees.last_working_day', null)
                    ->where('leave_balances.year',$currentyear)
                    ->orderBy('leave_balances.user_id', 'ASC');
            if(!empty($request->search)) {
                $leaves = $leaves->where('employees.first_name', 'LIKE', "%{$request->search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$request->search}%");
            }
            if(!empty($request->pagevalue)) {
                $leaves = $leaves->paginate($request->pagevalue);
            } else {
                $leaves = $leaves->paginate(10);
            }
        }

        foreach($leaves  as  $leave){
            $leave->count1 = Leave::where('employee_id',$leave->user_id)
			// ->whereYear('start_date', '=', $newYear)
			->where('start_date', '>=', $newYear.'-04-01')
			->where('start_date', '<=', date('Y-m-d'))
			->where(function ($query) {
                $query->where('status','A')
                ->orWhere('status','AU')
                ->orWhere('status', 'CA');
          })->count();

            // count total halfday leaves
            // $leave->halfday = Leave::where('employee_id',$leave->user_id)
			// ->where('start_date', '>=', $newYear.'-04-01')
			// ->where('start_date', '<=', date('Y-m-d'))
            // ->where('day', 'halfday')
			// ->where(function ($query) {
			// $query->where('status','A')
			// 	  ->orWhere('status','AU')
            //       ->orWhere('status', 'CA');
			// })->count();

            // count total fullday or null leaves
            // $leave->fullday = Leave::where('employee_id',$leave->user_id)
			// ->where('start_date', '>=', $newYear.'-04-01')
			// ->where('start_date', '<=', date('Y-m-d'))
            // ->where('day', 'fullday' || 'day', null)
			// ->where(function ($query) {
			// $query->where('status','A')
			// 	  ->orWhere('status','AU')
            //       ->orWhere('status', 'CA');
			// })->count();


            $UsedLeaveBalance = Leave::where('employee_id',$leave->user_id)
			->where('start_date', '>=', $newYear.'-04-01')
			->where('start_date', '<=', date('Y-m-d'))
			->where(function ($query) {
                $query->where('status','A')
                ->orWhere('status','AU')
                ->orWhere('status', 'CA');
            })->get();

            // if($leave->halfday > 0) {
            //     $leave->halfday = $leave->halfday/2;
            // } else {
            //     $leave->halfday = $leave->halfday;
            // }

            $total_leaves = $this->calculateAviled($newYear,$leave->user_id);

            if($leave->availed == null) {
                $leave->availed = 0;
            }
            $leave->total_availed = $leave->availed + $total_leaves;
            $leave->remaining = 0;
            $leave->remaining = $leave->entitled - $leave->total_availed;
            $UsedBalancecount = 0;
            foreach($UsedLeaveBalance as $leav){
                $UsedBalancecount += $this->getleavecounts($leav->start_date,$leav->end_date,$leav->day);

            }
            // return $UsedBalancecount;
            $leave->count = $UsedBalancecount;
            $leave->total = $leave->entitled - $UsedBalancecount;
            $leave->employee = Employee::where('id',$leave->user_id)->get();
            $leave->currentyear = $currentyear;

			if(!empty($year)){

				$mydate = $year."-01-01";
				$lastyear = strtotime("+1 year", strtotime($mydate));

				$leave->year =  $year.'-'. date("y", $lastyear);

			}else {
				$leave->year =  $leave->year.'-'. date('y', strtotime('+1 year'));
			}

			// $leave->year =  $leave->year.'-'. date('Y', strtotime('+1 year'));


            //$leave->entitled = $leave->entitled;
            //$leave->UsedBalancecount = $UsedBalancecount;
        }
       return $this->sendResponse($leaves,'Leave list');

    }

    public function addleavebalance(request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'entitled' => 'required',
            'carrired_forward' => 'required',
          ]);

          if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $current_year = date('Y');
        $previous_year = date("Y",strtotime("-1 year"));
        $month = Carbon::now()->month;
        if($month >= 3)
        {
            $year = $current_year;
        }else{
            $year = $previous_year;
        }

        // Each entry should be identified for each employee id with respect to financial year
        if (LeaveBalance::where('user_id', $request->user_id)->where('year', $year)->exists()) {
            return $this->sendResponse($year,'Employee already exists for this financial year.');
        }

        // entitled should be absolute || positive number
        if($request->entitled < 0) {
            $request->entitled = $request->entitled * -1;
        }

        // carried forward should be absolute || positive number
        if($request->carrired_forward < 0) {
            $request->carrired_forward = $request->carrired_forward * -1;
        }

        $leave_balance = new LeaveBalance;
        $leave_balance->user_id = $request->user_id;
        $leave_balance->year = $year;
        $leave_balance->entitled = $request->entitled;
        $leave_balance->carrired_forward = $request->carrired_forward;
        $leave_balance->save();

       return $this->sendResponse($leave_balance,'Leave Balance Add');
    }

    public function leavebalancedelete($id)
   {
       $leave = LeaveBalance::find($id);
       $leave->delete();
       return response()->json($leave);
   }

   public function editleavebalance($id)
   {
       $leave = LeaveBalance::find($id);


       if (is_null($leave)) {
           return $this->sendError('leave not found.');
       }
       return response()->json($leave);
   }

   public function leavebalanceupdate(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'user_id' => 'required',
            'entitled' => 'required',
            'carrired_forward' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $leave = LeaveBalance::find($id);
        $leave->user_id = $request->user_id;
        $leave->entitled = $request->entitled;
        $leave->carrired_forward = $request->carrired_forward;
        $leave->save();
        return response()->json($leave);
    }

    public function leavestatus(request $request)
    {
        $leave = Leave::where('id',$request->id)->first();
        $leave->status = $request->status;

		if($request->status == 'A' || $request->status == 'AU' )
        {
			$year = date('Y');
			$month = date('m');

			if($month <= 03){
				$year2 = $year-1;
			}else{
				$year2 = $year;
			}

			$leavecount = Leave::where('employee_id',$leave->employee_id)
			->whereYear('start_date', '=', $year)
			->where(function ($query) {
			$query->where('status','A')
             ->orWhere('status','AU');
			})->count();

			$leave->remaining = $leavecount + 1;
		}
        $leave->comment = $request->comment;
        $leave->save();


        $emp_id = $leave->employee_id;
        $leave_type = $leave->leave_type;
        $title = $leave->title;
        $start = $leave->start_date;
        $end = $leave->end_date;
        $leavestatus = '';


		$notification = new Notification;
		if($request->status == 'A' || $request->status == 'AU') {
			$notification->title = $title.'<br> Your leave has been approved <br>'. $start .'-'.$end ;
		}else if($request->status == 'C'){
			$notification->title = $title.'<br> Your leave has been rejected <br>'. $start .'-'.$end ;
		}
		$notification->description = $request->comment;
		$notification->type = 'L';
		$notification->employee_id = $request->employee_id;

		$notification->save();

        if($request->status == 'A' || $request->status == 'AU')
        {
            $leavestatus = 'Your Leave is Approved';
            $project = Project::where('project_name','Calidig-Internal')->first();
            $project_id = $project->id;
            $current_date = $start;
            while(strtotime($current_date) < strtotime($end))
            {
				$current_date= date("Y-m-d",strtotime("+1 day",strtotime($current_date)));
				$dsr = new DailyReport;
				$dsr->project_id = $project_id;
				$dsr->description = 'leave';
				$dsr->start_date = $current_date;
				$dsr->user_id = $emp_id;
				$dsr->save();
            }
        }
        if($request->status == 'C')
        {
            $leavestatus = 'Your Leave is Rejected';
        }
		if($request->status == 'CA')
        {
            $leavestatus = 'Leave is Cancel';
        }
        $emp = Employee::where('id',$request->employee_id)->first();
        $firstname = $emp->first_name;
        $lastname = $emp->last_name;
        $email = $emp->email;

        $data['subject'] = 'Leave Status';
        $data['name'] = $firstname.' '.$lastname;
        $data['email'] = $email;
        $data['type'] = $leave_type;
        $data['title'] = $title;
        $data['status'] = $leavestatus;
        $data['date'] = $start. ' to ' .$end;
        $data['url'] = env('SITE_URL');

		if($request->status != 'CA') {

			if (strpos($data['email'], 'calidig.com') !== false) {
					$data['email'] = $data['email'].'.test-google-a.com';
				}

			Mail::send('email/employee-leave', $data, function($message) use ($data) {
			$message->to($data['email'])
			->subject($data['subject']);
			$message->from('noreply@calidig.com','No Reply');
			});
		}

       return $this->sendResponse($leave,'Leave status Add');
    }

    //    ----------------------------------------
    // send notification to employee regarding leave status

    public function notifyLeaveStatus(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'employee_id' => 'required',
            'type' => 'required',
          ]);

          if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

		$notification = new Notification;

		$notification->title = $request->title;
		$notification->description = $request->description;
		$notification->type = $request->type;
		$notification->employee_id = $request->employee_id;

		$notification->save();

		return $this->sendResponse($notification,'Leave Notification sent!');

    }
    public function leavefilter(Request $request)
    {
        $start_date = $request->start_date ? $request->start_date: '';
        $end_date = $request->end_date ? $request->end_date: '';
        $date = $request->date? $request->date: '';
        $search =$request->search? $request->search: '';

        $leave = Leave::select('leaves.*','employees.first_name','employees.last_name')
            ->join('employees','leaves.employee_id','employees.id');

        if(!empty($start_date) && !empty($end_date) && !empty($search)) {
            $leave = $leave->whereDate('leaves.start_date', '>=', $start_date)
            ->whereDate('leaves.end_date', '<=', $end_date)
            ->where('employees.first_name', 'LIKE', "%{$search}%")
            ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }else if(empty($start_date) && !empty($end_date) && !empty($search)) {
            $leave = $leave->whereDate('leaves.end_date', '<=', $end_date)
            ->where('employees.first_name', 'LIKE', "%{$search}%")
            ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        } else if(!empty($start_date) && empty($end_date) && !empty($search)) {
            $leave = $leave->whereDate('leaves.start_date', '>=', $start_date)
            ->where('employees.first_name', 'LIKE', "%{$search}%")
            ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }else if(!empty($start_date) && !empty($end_date) && empty($search)) {
            $leave = $leave->whereDate('leaves.start_date', '>=', $start_date)
            ->whereDate('leaves.end_date', '<=', $end_date);
        }else if(empty($start_date) && empty($end_date) && !empty($search)) {
            $leave = $leave->whereDate('leaves.start_date', '>=', $start_date)
            ->where('employees.first_name', 'LIKE', "%{$search}%")
            ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        } else if(empty($start_date) && empty($end_date) && empty($search)) {
            $leave = $leave;
        }
        if($date=='week'){
            $leave = $leave->whereBetween('start_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        }
        elseif($date=='month'){
            $leave = $leave->whereMonth('start_date', Carbon::now()->month);
        }
		elseif($date=='today'){
            $leave = $leave->where('start_date', '<=', date('Y-m-d'))
				->where('end_date', '>=', date('Y-m-d'));
        }
        elseif($date=='requested'){

           $leave = $leave->where('leaves.status','I');
        }
        $leave = $leave->orderBy('leaves.id', 'DESC')->paginate(20);
		$year = date('Y');
		$month = date('m');

		if($month <= 03){
			$year2 = $year-1;
		}else{
			$year2 = $year;
		}
		foreach($leave as $le) {
			$le->balance = LeaveBalance::where('user_id',$le->employee_id)->where('leave_balances.year',$year2)->sum('entitled');
			$le->usedleave = Leave::where('employee_id',$le->employee_id)->where('start_date', '>=', $year2.'-04-01')
			->where('end_date', '<=', date('Y-m-d'))->where('status','A')->count();

            $le->apply = Leave::whereYear('start_date', '=', $year)->where('status','I')->count();
            $le->weakleave = Leave::whereBetween('start_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
			$le->monthleave = Leave::whereMonth('start_date', Carbon::now()->month)->count();
            $le->leavecount = $this->getleavecounts($le->start_date,$le->end_date,$le->day);

            $UsedLeaveBalance = Leave::where('employee_id',$le->employee_id)->whereYear('start_date', '=', $year)->where('status','A')->get();
            $le->UsedBalancecount = 0;
            foreach($UsedLeaveBalance as $leav){
                $le->UsedBalancecount += $this->getleavecounts($leav->start_date,$leav->end_date,$leav->day);
            }
            $le->remaining = $le->entitled - $le->UsedBalancecount;
		}


		$response = array('leave'=>$leave);

        return $this->sendResponse($response,'Leave list');

    }
    public function guidelines(){
        $guideline = Guideline::find(1);
        return response()->json($guideline);
    }
    public function editguideline($id)
    {
        $leaveguideline = Guideline::find($id);


        if (is_null($leaveguideline)) {
            return $this->sendError('Guideline not found');
        }
        return response()->json($leaveguideline);
    }
    public function guidelineupdate(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'guidelines' => 'required',
          ]);

          if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $guideline = Guideline::find(1);
        if($request->guidelines){
            $guideline->guidelines = $request->guidelines;
        }
        $guideline->save();


		$notification = new Notification;
		$notification->title = 'New leave guidline';
		$notification->description = 'New leave guidline update please check';
		$notification->type = 'L';

		$notification->save();

        return response()->json($guideline);

    }

    public function calculateAviled($year2, $user_id) {
        $unpaid_leaves_data = Leave::where('employee_id', $user_id)
        ->where('start_date', '>=', $year2.'-04-01')
        ->where('start_date', '<=', date('Y-m-d'))
        ->where(function ($query) {
        $query->where('status','A');
        })->get();
        $total_leavedays = 0;
        foreach($unpaid_leaves_data as $data) {
            if($data->day == 'halfday') {
                $total_leavedays= $total_leavedays + 0.5;
            } else {
                $startDate = Carbon::parse($data->start_date);
                $endDate = Carbon::parse($data->end_date);
                $diff = $endDate->diffInDays($startDate);
                $total_leavedays = $total_leavedays+$diff+1;
            }
        }
        return $total_leavedays;
   }

   public function checkTodaysLeave($date, $uid) {
        $leaveData = Leave::where('employee_id', $uid)
        ->where('start_date', $date)
        ->orwhere('end_date', $date)
        ->get();

        $isOnleave = false;
        if(!empty($leaveData)) {
            foreach($leaveData as $leave){
                if($leave->day == 'halfday') {
                    $isOnleave = false;
                } else {
                    if($leave->status == 'A' || $leave->status == 'AU') {
                        $isOnleave = true;
                    }
                    else {
                        $isOnleave = false;
                    }
                }
            }
        } else {
            $isOnleave = false;
        }
        $reponse = ['employeeOnLeave' => $isOnleave];
        return $this->sendResponse($reponse,"Today's leave details");
   }
}
