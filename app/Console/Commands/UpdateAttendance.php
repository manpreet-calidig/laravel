<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateAttendance:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        \Log::info("update attendance cron is working!");
        if(date('l') != 'Saturday' && date('l') != 'Sunday'){
            $employee = Leave::where('start_date', '<=', date('Y-m-d'))
				->where('end_date', '>=', date('Y-m-d'))
                ->get();
            $date = date('Y-m-d');
            foreach($employee as $emp) {
                    $getAttendance = Attendance::where('date', $date)
                    ->where('employee_id', $emp->employee_id)->first();
                    $attendence = Attendance::find($getAttendance->id);
                    if($emp->status == 'A') {
                        if($emp->day == 'halfday'){
                            $attendence->status = 2;
                        }else {
                            $attendence->status = 1;
                        }
                    }else if($emp->status == 'AU') {
                        $attendence->status = 6;
                    }
                    $attendence->save();
                }
            }
    }
}
