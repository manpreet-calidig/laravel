<?php

namespace App\Http\Controllers\API;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Project;
use App\Models\EmployeeRole;
use App\Models\Leave;
use App\Models\LeaveBalance;
use DB;
use Carbon\Carbon;


class DashboardController extends BaseController
{
   public function index() {
	   $user_id = \Request::get('user_id');
	   $type = \Request::get('type');

	   $today = date('Y-m-d');
      $employee = Employee::where('last_working_day', '>=' , $today)
      ->orWhere('last_working_day', null)->count();
      $client = Client::count();
	  $carried = 0;
	  $previous_year = date("Y",strtotime("-1 year"));
      $next_year = date("y",strtotime("+1 year"));
      $yearto =  '';
      $remaining = 0;
      $total_availed = 0;
      $unpaid_leaves = 0;
	  if($type != 'employee'){
		$project = Project::count();
		$UsedleaveBalance = "";
		$UsedleaveBalancecount = "";
		$total = "";
		$balance = "";
		$projectlist = Project::orderBy('id', 'DESC')->paginate(10);
	  }else{
		 $year = date('Y');
		 $month = date('m');
		 $current_year = date('Y');

			$month = Carbon::now()->month;
			if($month <= 3)
			{
				$preyear = $previous_year;

			}else{
				$preyear = $current_year;
			}

			if($month <= 03){
				$yearto = $previous_year.'-'.date('Y');
				$year2 = $year-1;
			}else{
				$yearto = date('Y').'-'.$next_year;
				$year2 = $year;
			}
		 $project = Project::whereRaw('FIND_IN_SET(?,assign_employee)', [$user_id])->count();
		 $leaveBalance = LeaveBalance::where('user_id',$user_id)->where('year',$preyear)->first();
		 $balance = 0;
		 if(!empty($leaveBalance)) {
			 $balance = $leaveBalance->entitled - $leaveBalance->carrired_forward;
             $carried = $leaveBalance->carrired_forward;
             $availed = $leaveBalance->availed;
             $total = $leaveBalance->entitled;
             $entitled = $leaveBalance->entitled;
		 }
		 $UsedleaveBalance = [];

		$UsedLeaveBalance = Leave::where('employee_id',$user_id)
		->where('start_date', '>=', $year2.'-04-01')
		->where('start_date', '<=', date('Y-m-d'))
		->where(function ($query) {
			$query->where('status','A')
            ->orWhere('status','AU')
                        ->orWhere('status', 'CA');
            })->get();

        $leave_count = Leave::where('employee_id',$user_id)
        // ->whereYear('start_date', '=', $newYear)
        ->where('start_date', '>=', $year2.'-04-01')
        ->where('start_date', '<=', date('Y-m-d'))
        ->where(function ($query) {
        $query->where('status','A')
           ->orWhere('status','AU')
           ->orWhere('status', 'CA');
        })->count();

        $unpaid_leaves = $this->calculateUnpaidLeaves($year2,$user_id);

        $total_leaves = $this->calculateAviled($year2,$user_id);

        if($availed == null) {
            $availed = 0;
        }
        $total_availed = $availed + $total_leaves;
        $remaining = $entitled - $total_availed;

        $UsedleaveBalancecount = 0;
		foreach($UsedLeaveBalance as $leav){
            $UsedleaveBalancecount += $this->getleavecounts($leav->start_date,$leav->end_date,$leav->day);
		}

		 $projectlist = Project::whereRaw('FIND_IN_SET(?,assign_employee)', [$user_id])->orderBy('id', 'DESC')->paginate(10);
	  }
      $employeerole = EmployeeRole::count();


	  $today = Leave::where('start_date', '<=', date('Y-m-d'))
				->where('end_date', '>=', date('Y-m-d'))
				->count();

	  $response = array(
        'employee'=> $employee,
        'client'=> $client,
        'project'=> $project,
        'employeerole'=> $employeerole,
        'projectlist'=> $projectlist,
        'usedleavebalance'=> $UsedleaveBalancecount,
        'balance'=> $balance,
        'today'=> $today,
        'total' => $total,
        'carried' => $carried,
        'yearto'=> $yearto,
        'remaining'=> $remaining,
        'total_availed'=> $total_availed,
        'unpaid_leaves'=> $unpaid_leaves
    );
	  return $this->sendResponse($response,'Count List');
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

   public function calculateUnpaidLeaves($year2, $user_id) {
        $unpaid_leaves_data = Leave::where('employee_id', $user_id)
        ->where('start_date', '>=', $year2.'-04-01')
        ->where('start_date', '<=', date('Y-m-d'))
        ->where(function ($query) {
        $query->where('status','AU');
        })->get();
        // return $unpaid_leaves_data;
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

}
