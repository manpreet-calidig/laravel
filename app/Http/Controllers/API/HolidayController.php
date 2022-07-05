<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

use App\Models\Holiday;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Notification;

use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;
use \Carbon\Carbon;
use Mail;

class HolidayController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */


    public function index(Request $request){
       $search = $request->search;
       $pagevalue = $request->pagevalue;
        $holiday = Holiday::where('id','!=',0);
        $holiday = $holiday->where('title', 'LIKE', "%{$search}%")
                            ->orWhere('holiday_type', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%");

        if(!empty($pagevalue)){
            $holiday = $holiday->orderBy('start_date')->paginate($pagevalue);
        }else{
            $holiday = $holiday->orderBy('start_date')->paginate(10);
        }
       return $this->sendResponse($holiday,'Holiday list');
    }
    public function holidaycalender(request $request){
		$month =$request->month;
		$year =$request->year;
		if(!empty($month) && !empty($year)){
			$start = date($year."-".(($month < 10)?"0".$month:$month)."-01");
			$end = date($year."-".(($month < 10)?"0".$month:$month)."-t");
		}else{
			$startDate = Carbon::now();
			$starts = $startDate->firstOfMonth()->format('Y-m-d');
			$start = date($starts);
			$endDate = Carbon::now();
			$ends = $endDate->endOfMonth()->format('Y-m-d');
			$end = date($ends);
		}

		$getmonth = date("m", strtotime($start));
		$getYear = date("Y", strtotime($start));

        $holiday = Holiday::select("*")
                        ->whereBetween('start_date', [$start, $end])
                        ->get();
        $newdata = [];
        $imageURL = env('APP_URL').'holiday.jpg';
        foreach($holiday as $data){
            $newdata[] = array('title'=>$data->title,'start'=>$data->start_date,'end'=>$data->end_date,'imageURL'=>$imageURL,'type'=>'holiday');
        }

		$responses = Employee::whereMonth('dob', '=', $getmonth)->get();
		$imageURL = env('APP_URL').'birthday.jpg';
		foreach($responses as $res){

			$birthdate = $getYear.date("-m-d", strtotime($res->dob));
			$newdata[] = array('title'=>$res->first_name.' '.$res->last_name.' Birthday','start'=>$birthdate,'end'=>$birthdate,'imageURL'=>$imageURL,'type'=>'birthday');
		}

		// $q->whereDay('created_at', '=', date('d'));
// $q->whereMonth('created_at', '=', date('m'));
        return $this->sendResponse($newdata,'Holiday list');
    }

    public function addholiday(request $request){
       $validator = Validator::make($request->all(), [

           'title' => 'required',
           'description' => 'required',
           'start_date' => 'required',
           'end_date' => 'required',
           'holiday_type' => 'required',
         ]);

         if($validator->fails()){
           return $this->sendError('Validation Error.', $validator->errors());
       }

       $holiday = new Holiday;

           $holiday->title = $request->title;
           $holiday->description = $request->description;
           $holiday->start_date = $request->start_date;
           $holiday->end_date = $request->end_date;
           $holiday->holiday_type = $request->holiday_type;
       $holiday->save();

       $title = $holiday->title;
       $description = $holiday->description;
       $notification = new Notification;
       $notification->title = $title;
       $notification->description = $description;
       $notification->save();

       $employee = Employee::all();
       foreach($employee as $emp)
       {
            $email = $emp->email;
            $firstname = $emp->first_name;
            $lastname = $emp->last_name;

			$data = [
				'subject' => 'New Holiday:'.$request->title,
				'name' => $firstname. ' ' .$lastname,
				'email' => $email,
				'date' => $request->start_date. ' to ' .$request->end_date,
				'url' => env('SITE_URL')
			  ];
              if (strpos($data['email'], 'calidig.com') !== false) {
                $data['email'] = $data['email'].'.test-google-a.com';
            }
			  Mail::send('email/holiday', $data, function($message) use ($data) {
				$message->to($data['email'])
                ->subject($data['subject']);
                $message->from('noreply@calidig.com','No Reply');
			  });
       }

       $Clients = Client::all();
       foreach($Clients as $Client)
       {
            $email = $Client->email;
            $firstname = $Client->first_name;
            $lastname = $Client->last_name;

			$data = [
				'subject' => 'New Holiday:'.$request->title,
				'name' => $firstname. ' ' .$lastname,
				'email' => $email,
				'date' => $request->start_date. ' to ' .$request->end_date,
				'url' => env('SITE_URL')
			  ];
              if (strpos($data['email'], 'calidig.com') !== false) {
                $data['email'] = $data['email'].'.test-google-a.com';
            }
			  Mail::send('email/holiday', $data, function($message) use ($data) {
				$message->to($data['email'])
                ->subject($data['subject']);
                $message->from('noreply@calidig.com','No Reply');
			  });
       }
       return response()->json($holiday);

   }
   public function destroy($id)
   {
       $holiday = Holiday::find($id);
       $holiday->delete();

       return response()->json($holiday);

   }
   public function edit($id)
    {
        $holiday = Holiday::find($id);


        if (is_null($holiday)) {
            return $this->sendError('holiday not found.');
        }
        return response()->json($holiday);
    }

    public function update(Request $request, $id)
    {

        $input = $request->all();

        $validator = Validator::make($input, [
            'title' => 'required',
            'description' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'holiday_type' => 'required',
        ]);


        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }


        $holiday = Holiday::find($id);

        $holiday->title = $request->title;
        $holiday->description = $request->description;
        $holiday->start_date = $request->start_date;
        $holiday->end_date = $request->end_date;
        $holiday->holiday_type = $request->holiday_type;
        $holiday->save();

        return response()->json($holiday);

    }

}
