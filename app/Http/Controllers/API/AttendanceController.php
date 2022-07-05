<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\SalaryRecords;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;
use DB;
use Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request as FacadesRequest;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum;

class AttendanceController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */


    public function index(Request $request)
    {
        $id = $request->id ? $request->id : '';
        $date = $request->date ? $request->date : '';
        $search = $request->search ? $request->search : '';
        $emp_id = $request->emp_id ? $request->emp_id : '';
        $month = $request->month ? $request->month : '';
        $status = $request->status ? $request->status : '';
        $pagevalue = $request->pagevalue ? $request->pagevalue : '';
        $user_id = \Request::get('user_id');
        $date = $request->date;
        $data = '';
        if (!empty($id)) {
            $Emp_id = $id;
        }else {
            $Emp_id = '';
        }

        $response = Attendance::select('attendances.*', 'attendances.status as attendance_status', 'employees.first_name', 'employees.last_name', 'employees.designation', 'employees.email', 'employees.image', 'employees.join_date', 'employees.streams')
            ->join('employees', 'attendances.employee_id', 'employees.id');

        if (!empty($Emp_id) && !empty($search) && empty($status) && empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->where('employee_id', $Emp_id)->where('employees.first_name', 'LIKE', "%{$search}%")
            ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (empty($Emp_id) && !empty($search) && empty($status) && empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->where('employees.first_name', 'LIKE', "%{$search}%")
            ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        
        /* Attendance by leave status  */
        
        else if (!empty($Emp_id) && !empty($status) && !empty($month) && $date == 'null') {
			    $data = $response->where('employee_id', $Emp_id)
                ->where('attendances.status', $status)
                ->whereMonth('attendances.date', $month);
        }
        
        else if (!empty($Emp_id) && !empty($search) && !empty($status) && empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->where('employee_id', $Emp_id)
                ->where('attendances.status', $status)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (empty($Emp_id) && !empty($search) && !empty($status) && empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response
                ->where('attendances.status', $status)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        //get attendance for searched status
        else if (empty($Emp_id) && empty($search) && !empty($status) && empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->where('attendances.status', $status);
        }
        // get attendance for searched month
        else if (empty($Emp_id) && empty($search) && empty($status) && !empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->whereMonth('attendances.date', $month);
        }
        //get attendance for searched date
        else if (empty($Emp_id) && empty($search) && empty($status) && empty($month) && (!empty($date) && $date != 'null')) {
            $data = $response->whereDate('attendances.date', $date);
        } else if (!empty($Emp_id) && !empty($search) && empty($status) && empty($month) && (!empty($date) && $date != 'null' || empty($date))) {
            $data = $response->where('employee_id', $Emp_id)
                ->whereDate('attendances.date', $date)
                ->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (empty($Emp_id) && !empty($search) && empty($status) && empty($month) && (!empty($date) && $date != 'null' || empty($date))) {
            $data = $response
                ->whereDate('attendances.date', $date)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (!empty($Emp_id) && !empty($search) && empty($status) && !empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->where('employee_id', $Emp_id)
                ->whereMonth('attendances.date', $month)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (empty($Emp_id) && !empty($search) && empty($status) && !empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response
                ->whereMonth('attendances.date', $month)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        } else if (!empty($Emp_id) && !empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->where('employee_id', $Emp_id)
                ->whereMonth('attendances.date', $month)
                ->where('attendances.status', $status)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (empty($Emp_id) && !empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response
                ->whereMonth('attendances.date', $month)
                ->where('attendances.status', $status)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        } else if (!empty($Emp_id) && !empty($search) && !empty($status) && empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->where('employee_id', $Emp_id)
                ->where('attendances.status', $status)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (empty($Emp_id) && !empty($search) && !empty($status) && empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response
                ->where('attendances.status', $status)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        } else if (!empty($Emp_id) && !empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response->where('employee_id', $Emp_id)
                ->where('attendances.status', $status)
                ->whereMonth('attendances.date', $month)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (empty($Emp_id) && !empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response
                ->where('attendances.status', $status)
                ->whereMonth('attendances.date', $month)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        } else if (!empty($Emp_id) && !empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date != 'null')) {
            $data = $response->where('employee_id', $Emp_id)
                ->where('attendances.status', $status)
                ->whereMonth('attendances.date', $month)
                ->whereDate('attendances.date', $date)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }
        else if (empty($Emp_id) && !empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date != 'null')) {
            $data = $response
                ->where('attendances.status', $status)
                ->whereMonth('attendances.date', $month)
                ->whereDate('attendances.date', $date)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }else if (!empty($Emp_id) && !empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date != 'null')) {
            $data = $response->where('employee_id', $Emp_id)
                ->where('attendances.status', $status)
                ->whereMonth('attendances.date', $month)
                ->whereDate('attendances.date', $date)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        }else if (empty($Emp_id) && !empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date != 'null')) {
            $data = $response
                ->where('attendances.status', $status)
                ->whereMonth('attendances.date', $month)
                ->whereDate('attendances.date', $date)->where('employees.first_name', 'LIKE', "%{$search}%")
                ->orWhere('employees.last_name', 'LIKE', "%{$search}%");
        } else if (empty($Emp_id) && empty($search) && !empty($status) && !empty($month) && (!empty($date) && $date == 'null' || empty($date))) {
            $data = $response
                ->where('attendances.status', $status)
                ->whereMonth('attendances.date', $month);
        }
        else {
            $date = $response;
        }
        if(!empty($id)) {
            $date = $response->where('employee_id', $Emp_id);
        }
        if (!empty($pagevalue)) {
            $data = $response->orderBy('id', 'DESC')->paginate($pagevalue);
        } else {
            $data = $response->orderBy('id', 'DESC')->paginate(10);
        }
        return $this->sendResponse($data, 'Attendance list');
    }
    public function attendancechart(Request $request)
    {

        $filter = $request->filter;
        $emp_id = $request->id;
        $currentmonth = $request->month;
        $start = Carbon::now()->startOfMonth();
        $end = date('Y-m-d');

        if (!empty($currentmonth)) {

            $start_date = date('Y-m-01', strtotime($currentmonth));
            $end_date = date('Y-m-t', strtotime($currentmonth));
            $month = date('m', strtotime($currentmonth));
            $year = date('Y', strtotime($currentmonth));
            if ($month == date('m')) {
                $currentmonth = date('m');
                $day = $this->getleavecounts($start, $end);
                $present = Attendance::where('status', '5')->where('employee_id', $emp_id)->whereMonth('date', '=', $currentmonth)->count();
                $leave = Attendance::where('status', '1')->where('employee_id', $emp_id)->whereMonth('date', '=', $currentmonth)->count();
            } else {
                $day = $this->getleavecounts($start_date, $end_date);
                $present = Attendance::where('status', '5')->where('employee_id', $emp_id)->whereYear('date', '=', $year)->whereMonth('date', '=', $month)->count();
                $leave = Attendance::where('status', '1')->where('employee_id', $emp_id)->whereYear('date', '=', $year)->whereMonth('date', '=', $month)->count();
            }
        } else {
            $currentmonth = date('m');
            $day = $this->getleavecounts($start, $end);
            $present = Attendance::where('status', '5')->where('employee_id', $emp_id)->whereMonth('date', '=', $currentmonth)->count();
            $leave = Attendance::where('status', '1')->where('employee_id', $emp_id)->whereMonth('date', '=', $currentmonth)->count();
        }

        $response = ['day' => $day, 'present' => $present, 'leave' => $leave];
        return $this->sendResponse($response, 'Attendance list');
    }

    public function getleavecounts($startdate, $enddate, $day = '')
    {
        $start = new \DateTime($startdate);
        $end = new \DateTime($enddate);
        // otherwise the  end date is excluded (bug?)
        $end->modify('+1 day');

        $interval = $end->diff($start);

        // total days
        $days = $interval->days;

        // create an iterateable period of date (P1D equates to 1 day)
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);

        // best stored as array, so you can add more than one
        $holidays = array('2012-09-07');

        foreach ($period as $dt) {
            $curr = $dt->format('D');

            // substract if Saturday or Sunday
            if ( $curr == 'Sat' || $curr == 'Sun') {
                $days--;
            }

            // (optional) for the updated question
            elseif (in_array($dt->format('Y-m-d'), $holidays)) {
                $days--;
            }
        }
        if (!empty($day)) {
            if ($day == 'halfday') {
                $days = $days - 0.5;
            }
        }

        return $days; // 4

    }

    public function getAppliedLeaves($emp_id, $today) {
        $appliedLeaves = Leave::where('employee_id', $emp_id)->where('start_date', '<=', $today)
        ->where('end_date', '>=', $today)->where('status', 'I')->count();
        return $appliedLeaves;
    }

    public function getWorkingEmployees(Request $request){
        $month = $request->month? $request->month : '';
        $search = $request->emp? $request->emp : '';
        $pagevalue = $request->pagevalue;
        $year = $request->year;
        $day = Carbon::now()->format('d');
        $month__ = Carbon::now()->format('m');
        $year_ = Carbon::now()->format('Y');
        if($year == '' || $year == null || empty($year)) {
            $year = $year_;
        }
        $today = ($year.'-'.$month__.'-'.$day);
        $today__ = $today;
        $employee = Employee::where('id','!=',0); {
        $employees = $employee->where('last_working_day', '>=' , $today)
        ->orWhere('last_working_day', null);
        }
        if(!empty($search)) {
            $employee = $employee->where('first_name', 'LIKE', "%{$search}%")
            ->orWhere('last_name', 'LIKE', "%{$search}%")
            ->where(function($query)use($today) {
            $query->where('last_working_day', '>=', $today)
            ->orWhere('last_working_day', null);
            }
            );
        }
        if(empty($pagevalue)) {
            $employees = $employee->paginate(50);
        } else {
            $employees = $employee->paginate($pagevalue);
        }
        foreach($employees as $emp) {
            $emp->percentageData = $this->calculateAttendancePercentage($emp->id, $month, $year, $today__);
        }
        return $employees;
    }

    public function getSalaryStatus($emp_id, $month, $year) {
        $salaryData = SalaryRecords::where('emp_id', $emp_id)->whereMonth('date', $month)->whereYear('date', $year)->first();
        return $salaryData;
    }

    public function calculateAttendancePercentage($emp_id, $month, $year, $today__) {
        if(empty($month)) {
            $currentmonth = date('m');
        } else {
            $currentmonth = $month;
        }
        $currentyear = $year;
        $currentyear__ = date('Y');
        $thisMonth = date('m');
        // '0 => absent/no dsr, 1 =>full day, 2 => half day, 3 =>leave, 4 => holiday, 5 => Present, 6 => Unpaid leave'
        $start_date = date($currentyear.'-'.$currentmonth.'-d H:i:s');
        $today = today();
        $assigned_leaves = LeaveBalance::where('year', $currentyear)->where('user_id', $emp_id)->sum('entitled');
        $assigned_leaves = (int)$assigned_leaves;
        $present_days = Attendance::where('status', '5')
                        ->where('employee_id', $emp_id)
                        ->whereMonth('date', '=', $currentmonth)
                        ->whereDate('date', '<=', $start_date)
                        ->count();

        $total_working_days = Attendance::where('employee_id', $emp_id)
                            ->whereMonth('date', '=', $currentmonth)
                            ->whereYear('date', '=', $currentyear)
                            ->count();
        $holidays = Attendance::where('status', '4')
                        ->where('employee_id', $emp_id)
                        ->whereMonth('date', '=', $currentmonth)
                        ->whereDate('date', '<=', $start_date)
                        ->count();
        $total_working_days = $total_working_days - $holidays;
        if($present_days <= 0) {
            $attendance_per = 0;
        } else {
            $attendance_per = round($present_days/$total_working_days * 100);
        }
        $appliedLeaves = $this->getAppliedLeaves($emp_id, $today);
        $salaryData = $this->getSalaryStatus($emp_id, $currentmonth, $year);
        $employees = Employee::where('last_working_day', '>=' , $today__)
         ->orWhere('last_working_day', null)->count();
                $details = [
                    'emp_id' => $emp_id,
                    'presentDays' => $present_days,
                    'workingDaysTillToday' => $total_working_days,
                    'attendancePercentage' => $attendance_per,
                    'assignedLeaves' => $assigned_leaves,
                    'leave_approval_pending' => $appliedLeaves,
                    'salaryData' => $salaryData,
                    'total_employees' => $employees
                ];
        return $details;
    }

    public function addSalary(Request $request) {
        $data_ = $request->all();
        $salary = new SalaryRecords;
        foreach($data_ as $sal) {
            $data = array(
                array('status' => $sal['status'],
                'emp_id' => $sal['emp_id'],
                'date' => $sal['date'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now())
                );
                // $emp = Employee::where('id',$sal['emp_id'])->first();
                // $firstname = $emp->first_name;
                // $lastname = $emp->last_name;
                // $employee_email = $emp->email;
                // $data1['subject'] = 'Monthy Salary Email';
                // $data1['name'] = $firstname.$lastname;
                // // $data1['employeeemail'] = $employee_email;
                // $data1['employeeemail'] = 'rakesh@calidig.com';
                // $data1['status'] = $sal['status'];
                // $data1['url'] = env('SITE_URL');
                // if (strpos($data1['employeeemail'], 'calidig.com') !== false) {
                //     $data1['employeeemail'] = $data1['employeeemail'].'.test-google-a.com';
                // }

                // Mail::send('email/salary', $data1, function($message) use ($data1) {
                // $message->to($data1['employeeemail'])
                // ->subject($data1['subject']);
                // $message->from('noreply@calidig.com','No Reply');
                // });

            $salary = SalaryRecords::insert($data);
        }
        return $this->sendResponse($salary,'Salary data');
    }

    public function updateSalaryByEmpId(Request $request)
    {
        $id = $request->id;
        $salary = SalaryRecords::find($id);
        $salary->status = $request['status'];
        $salary->emp_id = $request['emp_id'];
        $salary->date  = $request['date'];

        $emp = Employee::where('id',$request['emp_id'])->first();
        $firstname = $emp->first_name;
        $lastname = $emp->last_name;
        $employee_email = $emp->email;

        // $data['subject'] = 'Monthy Salary Email Update';
        // $data['name'] = $firstname.$lastname;
        // // $data['employeeemail'] = $employee_email;
        // $data['employeeemail'] = 'rakesh@calidig.com';
        // $data['status'] = $request['status'];
        // $data['url'] = env('SITE_URL');
        // if (strpos($data['employeeemail'], 'calidig.com') !== false) {
        //     $data['employeeemail'] = $data['employeeemail'].'.test-google-a.com';
        // }

        // Mail::send('email/salary', $data, function($message) use ($data) {
        // $message->to($data['employeeemail'])
        // ->subject($data['subject']);
        // $message->from('noreply@calidig.com','No Reply');
        // });
        $salary->update();
        return response()->json($salary);
    }

    public function todaysAttendance() {
        $today = today();
        $present = Attendance::where('status', '5')
        ->whereDate('date', '=', $today)
        ->count();
        $employee = Employee::where('id','!=',0)->where('last_working_day', '>=' , $today)
        ->orWhere('last_working_day', null)->count();

        $response = ['present' => $present, 'total' => $employee];
        return $this->sendResponse($response, 'Attendance list');
    }
}
