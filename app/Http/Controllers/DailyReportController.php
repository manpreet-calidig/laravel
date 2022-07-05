<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyReport;
use App\Exports\DailyReportExport;
use PDF;
use Mail;
use Maatwebsite\Excel\Facades\Excel;
class DailyReportController extends Controller
{
    public function dsrpdf(request $request)
    {
        $type = $request->type;

        $project_id = $request->project;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $emp_id = $request->employee;
        
        $dsr = DailyReport::select('daily_reports.*','projects.project_name','projects.client_id','employees.first_name')
        ->join('projects','daily_reports.project_id','projects.id')
        ->join('employees','daily_reports.user_id','employees.id');

        if($type == 'client'){
            $dsr1 = $dsr->get();
           foreach ($dsr1 as $data) {
               if($data->client_id == $emp_id) {
                   $dsr = $dsr->where('projects.client_id',$data->client_id);
                   $dsr = $dsr->where('projects.project_name','!=','Calidig-Internal');
                   $dsr = $dsr->where('daily_reports.is_approved', 'Yes');
               }
           }
       }
        if($project_id != '' && $project_id != 'undefined' && $project_id != 'all'){
            $dsr = $dsr->where('daily_reports.project_id',$project_id);
        }
        if($type != 'client')
        {
            if($emp_id != '' && $emp_id != 'undefined' && $emp_id != 'all'){
                $dsr = $dsr->where('daily_reports.user_id',$emp_id);
            }
        }
        
        if($start_date != '' && $start_date != 'undefined'){
            $dsr = $dsr->whereBetween('daily_reports.start_date', [$start_date, $end_date]);
        }

        foreach($dsr as $res){
            $descriptions = trim($res->description);
            $exdesc = explode('---',$descriptions);
			$exhour = explode('---',$res->hour);
			$i = 0;
			$description = $hour = '';
			foreach($exdesc as $exdes){
				
				if(!empty($exdes)){
					$description .= "&#8226; ".strip_tags($exdes)."<br>";
				}
				
				$hour .=  "&#8226; ".$exhour[$i]."<br>";
				$i++;
			}
			
			$res->description = $description;
			$res->hour = $hour; 
        }

        $dsr = $dsr->get();
        view()->share('dsr',$dsr); 
          
        $pdf = PDF::loadView('myPDF');
    
        return $pdf->download('dsr.pdf');

    }

    public function fileExport(request $request) 
    {
        $type = $request->type;
        $project_id = $request->project;
        $emp_id = $request->employee;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        return Excel::download(new DailyReportExport($project_id,$emp_id,$start_date,$end_date,$type), 'DSR-collection.xlsx');
    } 
	
	public function emailtest() {
      $data = array('name'=>"Virat Gandhi");
   $mail = 'rohit@calidig.com';
   if (strpos($mail, 'calidig.com') !== false) {
    $mail = $mail.'.test-google-a.com';
}
      $email = Mail::send(['text'=>'mail'], $data, function($message) use ($mail) {
         $message->to($mail, 'Tutorials Point')->subject
            ('Laravel Basic Testing Mail');
         $message->from('noreply@calidig.com','No Reply');
      });
	  print_r($email);
      echo "Basic Email Sent. Check your inbox.";
   }
}
