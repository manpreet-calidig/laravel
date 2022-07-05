<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;


class ReportController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */


     public function index(){
        //  $report = Report::where('id','!=',0)->paginate(20);
        //$report = Report::select('reports.*','employees.first_name','employees.last_name')->leftJoin('employees','reports.employee_id','employees.id')->paginate(20);
        
        //return $this->sendResponse($report,'Report list');
		
		$user_id = \Request::get('user_id');
		$type = \Request::get('type');
		
		$permission = "";
		$managerinfo = Employee::select('employees.*','employee_roles.role','permissions.permission')->Join('employee_roles','employee_roles.id','employees.role')->Join('permissions','permissions.user_id','employee_roles.id')->where('employees.id',$user_id)->whereRaw('FIND_IN_SET(5,permissions.permission)')
		->first();

		$response = Report::select('reports.*','employees.first_name','employees.last_name')->leftJoin('employees','reports.employee_id','employees.id');
       
        if($type == 'employee'){
			if(empty($managerinfo)){
				$response->where('reports.manager_id',$user_id);
			}
		}
		
		$response = $response->paginate(20);
		if ($response->isEmpty()) {
			$response = Report::select('reports.*','employees.first_name','employees.last_name')->leftJoin('employees','reports.employee_id','employees.id')->paginate(20);
		}
		return $this->sendResponse($response,'Team list');
     }

    public function addreport(request $request){
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'description' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
          ]);

          if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
		
		$user_id = \Request::get('user_id');
		
        $report = new Report;
        $report->employee_id = $request->employee_id;
        $report->description = $request->description;
        $report->start_date = $request->start_date;
        $report->end_date = $request->end_date;
        $report->manager_id = ((empty($request->manager_id))?$user_id:$request->manager_id);
        $report->save();

    }

    public function edit($id){

        $report = Report::find($id);

        if (is_null($report)) {
            return $this->sendError('report not found.');
        }
        return response()->json($report);
    }

    public function destroy($id)
    {
        $report = Report::find($id);
        $report->delete();

        return response()->json($report);
    }

    public function update(Request $request, $id)
    {
       
        $input = $request->all();

        $validator = Validator::make($input, [
            'employee_id' => 'required',
            'description' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);


        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

		$user_id = \Request::get('user_id');
        $report = Report::find($id);
        $report->employee_id = $request->employee_id;
        $report->description = $request->description;
        $report->start_date = $request->start_date;
        $report->end_date = $request->end_date;
		$report->manager_id = ((empty($request->manager_id))?$user_id:$request->manager_id);
        $report->save();

        return response()->json($request->employee_id);

    }
}
