<?php

namespace App\Http\Controllers\API;
use App\Models\User;
use App\Models\Role;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\Leave;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;

use DB;

class LeaveTypeController extends BaseController
{
   public function index() {
    $leavetype = LeaveType::where('id','!=',0)->paginate(20);
	$user_id = \Request::get('user_id');
	$type = \Request::get('type');

	if($type == 'employee'){
		$year = date('Y');
		$month = date('m');
		if($month <= 03){
			$year2 = $year-1;
		}else{
			$year2 = $year;
		}

		$balance =  LeaveBalance::where('user_id',$user_id)->where('leave_balances.year',$year2)->sum('entitled');
		$apply = Leave::where('employee_id',$user_id)
		->where('start_date', '>=', $year2.'04-01')
		->where('start_date', '<=', date('Y-m-d'))
		->where(function ($query) {
			$query->where('status','A')
				  ->orWhere('status','AU');
		})
		->get();
		$apply_leave = 0;
		foreach($apply as $ress) {
			$apply_leave += $this->getleavecounts($ress->start_date,$ress->end_date,$ress->day);
		}

		$response = [
			'leavetype'=>$leavetype,
			'balance'=>$balance,
			'apply'=>$apply_leave
		];

		return $this->sendResponse($response,'leave type list');
	}else{
        $response = [
			'leavetype'=>$leavetype
		];
		return $this->sendResponse($response,'leave type list');
	}

   }

   public function leave_type(Request $request){
		$validator = Validator::make($request->all(), [
			'leave_type' => 'required',
		  ]);

		  if($validator->fails()){
			return $this->sendError('Validation Error.', $validator->errors());
		}

		$leave_type = new LeaveType;
		$leave_type->leave_type = $request->leave_type;
		$leave_type->save();

		return $this->sendResponse($leave_type,'leave type list');

   }

   public function destroy($id)
   {
       $leave_type = LeaveType::find($id);
       $leave_type->delete();

       return response()->json($leave_type);
   }

   public function edit($id)
   {
       $leave_type = LeaveType::find($id);


       if (is_null($leave_type)) {
           return $this->sendError('Leave type not found.');
       }
       return response()->json($leave_type);
   }

   public function update(Request $request, $id)
   {

       $input = $request->all();

       $validator = Validator::make($input, [
           'leave_type' => 'required',
       ]);


       if($validator->fails()){
           return $this->sendError('Validation Error.', $validator->errors());
       }


       $leave_type = LeaveType::find($id);

       $leave_type->leave_type = $request->leave_type;
       $leave_type->save();

       return response()->json($leave_type);
   }
}
