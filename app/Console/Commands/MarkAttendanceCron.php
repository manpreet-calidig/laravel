<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use Illuminate\Console\Command;
Use \Carbon\Carbon;

class MarkAttendanceCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'markattendance:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If No DSR filled by employee then this cron job will automatically mark attendance 0 for that employee for that specific date';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \Log::info("Cron is working fine!");
        $today = Carbon::now()->format('Y-m-d');
		if(date('l') != 'Saturday' && date('l') != 'Sunday'){
        $employee = Employee::where('last_working_day', '>=' , $today)->orWhere('last_working_day', null)->get();
        //$date = Carbon::now();
        $date = date('Y-m-d');
        //$getAttendance = Attendance::where('date', $date)->get();
        $attendance_list = [];
            foreach($employee as $emp) {
                $getAttendanceElse = Attendance::where('date', $date)->where('employee_id', $emp->id)->count();

                $getLeavesElse = Leave::where('start_date', '<=', date('Y-m-d'))
				->where('end_date', '>=', date('Y-m-d'))
                ->where('employee_id', $emp->id)

                ->first();

                $getHolidaysElse = Holiday::where('start_date','>=', date('Y-m-d'))
                ->where('end_date', '<=', date('Y-m-d'))
                ->count();

                    if($getAttendanceElse == 0) {
                        // check if holiday on the same date
                        if($getHolidaysElse > 0) {
                            array_push($attendance_list, [
                                'employee_id' => $emp->id,
                                'date' => $date,
                                'status' => 4,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ]);
                        }
                        // if elmployee is on leave
                        else if(!empty($getLeavesElse)) {


							if($getLeavesElse->status == 'A') {

								if($getLeavesElse->day == 'halfday'){
									array_push($attendance_list, [
									'employee_id' => $emp->id,
									'date' => $date,
									'status' => 2,
									'created_at' => Carbon::now(),
									'updated_at' => Carbon::now()
									]);
								}else {
									array_push($attendance_list, [
									'employee_id' => $emp->id,
									'date' => $date,
									'status' => 1,
									'created_at' => Carbon::now(),
									'updated_at' => Carbon::now()
									]);
								}

							}else if($getLeavesElse->status == 'AU') {

								array_push($attendance_list, [
									'employee_id' => $emp->id,
									'date' => $date,
									'status' => 6,
									'created_at' => Carbon::now(),
									'updated_at' => Carbon::now()
									]);
							} else {
								array_push($attendance_list, [
									'employee_id' => $emp->id,
									'date' => $date,
									'status' => 5,
									'created_at' => Carbon::now(),
									'updated_at' => Carbon::now()
								]);
							}

                        } else {
                            array_push($attendance_list, [
                                'employee_id' => $emp->id,
                                'date' => $date,
                                'status' => 5,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ]);
                        }
                    }
        }

        Attendance::insert($attendance_list);
		}
    }
}
