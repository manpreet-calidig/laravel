<?php

namespace App\Exports;

use App\Models\DailyReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
class DailyReportExport implements FromCollection, WithHeadings, WithDrawings, ShouldAutoSize
{
    protected $project_id;
    protected $emp_id;
    protected $start_date;
    protected $end_date;
    protected $type;



 function __construct($project_id,$emp_id,$start_date,$end_date,$type) {
        $this->project_id = $project_id;
        $this->emp_id = $emp_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->type = $type;

 }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $dsr = DailyReport::select('employees.first_name','projects.project_name','daily_reports.description','daily_reports.hour','daily_reports.start_date')
        ->join('projects','daily_reports.project_id','projects.id')
        ->join('employees','daily_reports.user_id','employees.id');

        if($this->type == 'client'){
            $dsr1 = $dsr->get();
           foreach ($dsr1 as $data) {
               if($data->client_id == $this->emp_id) {
                   $dsr = $dsr->where('projects.client_id',$data->client_id);
                   $dsr = $dsr->where('projects.project_name','!=','Calidig-Internal');
                   $dsr = $dsr->where('daily_reports.is_approved', 'Yes');
               }
           }
       }

        if($this->project_id != '' && $this->project_id != 'undefined' && $this->project_id != 'all'){
            $dsr = $dsr->where('daily_reports.project_id',$this->project_id);
        }

        if($this->type != 'client')
        {
            if($this->emp_id != '' && $this->emp_id != 'undefined' && $this->emp_id != 'all'){
                $dsr = $dsr->where('daily_reports.user_id',$this->emp_id);
            }
        }
        
        
        if($this->start_date != '' && $this->start_date != 'undefined'){
            $dsr = $dsr->whereBetween('daily_reports.start_date', [$this->start_date, $this->end_date]);
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
        return $dsr;
        // return DailyReport::all();
    }
    public function headings(): array
    {
        return [
            'Employee name',
            'Project name',
            'Description',
            'Hour',
            'Start date',
        ];
						
    }
    public function drawings()
    {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath(public_path('CALIDIG-SOLUTIONS-LOGO-2-1.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('G1');
        return $drawing;
    }
}
