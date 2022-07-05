<?php

namespace App\Http\Controllers\API;
use App\Models\User;
use App\Models\Overview;
use App\Models\Role;
use App\Models\Project;
use App\Models\Employee;
use App\Models\Client;
use App\Models\ProjectStatu;
use App\Models\Log;
use App\Models\Notification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Mail;

use DB;

use function PHPSTORM_META\type;

class OverviewController extends BaseController
{

    public function overview($id)
    {
		$user_id = \Request::get('user_id');
	    $type = \Request::get('type');

	   if($type != 'client'){
		$id = $this->encrypt($id,'D');
		$response = ProjectStatu::select('overviews.*','project_status.status as task_status','projects.project_name')->leftjoin('overviews','project_status.id','overviews.drag_drop_status')->leftJoin('projects','overviews.project_id','projects.id')->where('overviews.project_id',$id)->get();
		$data = [
			array('id'=>'Open','weeklist'=>array('<div class="hide"></div>')),
			array('id'=>'Re-Open','weeklist'=>array('<div class="hide"></div>')),
			array('id'=>'Ready','weeklist'=>array('<div class="hide"></div>')),
			array('id'=>'In Process','weeklist'=>array('<div class="hide"></div>')),
			array('id'=>'Testing','weeklist'=>array('<div class="hide"></div>')),
			array('id'=>'Done','weeklist'=>array('<div class="hide"></div>')),
			array('id'=>'Completed','weeklist'=>array('<div class="hide"></div>')),
			array('id'=>'Closed','weeklist'=>array('<div class="hide"></div>'))
		];
		$i = 0;
		foreach($response as $res) {
			$resid = $this->whatever($data,'id',$res->task_status);

			if($res->added_type == 'employee'){
				$addedby = Employee::where('id',$res->added_by)->first();
			}else{
				$addedby = Client::where('id',$res->added_by)->first();
			}
			$task = '';
			if($res->status == 'N'){
				$task .= '<span class="blue">New Task</span>';
			}else if($res->status == 'I'){
				$task .= '<span class="red">Issue</span>';
			}else if($res->status == 'B'){
				$task .= '<span class="red">Bug</span>';
			}else if($res->status == 'R'){
				$task .= '<span class="red">Re Issue</span>';
			}else if($res->status == 'C'){
				$task .= '<span class="green">Completed</span>';
			}

			if($resid){
				if($res->task_status != 'Closed') {
					$data[$resid-1]['weeklist'][] =  '<div class="tab-list"><span class="hide">'.$this->encrypt($res->id).'</span> <i class="hide">project_'.$res->id.'</i>  <p class="content-title" >'.$res->title.'</p> <p class="content-status">#'.$res->id.' Opened by '.$addedby->first_name.' '.$addedby->last_name.'</p> <p class="custom-task">'.$task.'</p></div>';
				}
				// $data[$resid-1]['overview_id'][] = $res->overview_id;
			}else{
				$data[$i]['id'] =  $res->task_status;
				if(!empty($res->id)){
					if($res->task_status != 'Closed') {
						//$data[$i]['weeklist'][$res->id] = '<div class="tab-list"><i class="hide">project_'.$res->id.'</i>  <p class="content-title" id="'.$res->id.'">'.$res->title.'</p> <p class="content-status">#'.$res->id.' Opened by '.$addedby->first_name.' '.$addedby->last_name.'</p> <p class="custom-task">'.$task.'</p></div>';
					}
				}
				$i++;
			}
			// $data['overview'] = $res->overview_id;
		}
	   }else {
		   $id = $this->encrypt($id,'D');
		   $data = ProjectStatu::select('overviews.*','project_status.status as task_status','projects.project_name')
		   ->leftjoin('overviews','project_status.id','overviews.drag_drop_status')
		   ->leftJoin('projects','overviews.project_id','projects.id')
		   ->where('overviews.project_id',$id)->paginate(20);
		   foreach($data as $datas) {
			   $datas->encryptid = $this->encrypt($datas->id);
		   }
	   }
        return $this->sendResponse($data,'Overview list');

	}

	function overview_detail($id){
		$id = $this->encrypt($id,'D');
		$overview = overview::where('id',$id)->first();

		if($overview->added_type == 'employee') {
			$overview->userinfo = Employee::find($overview->added_by);
		} else {
			$overview->userinfo = Client::find($overview->added_by);
		}


			if($overview->status == 'N'){
				$overview->status =  '<span class="blue">New Task</span>';
			}else if($overview->status == 'I'){
				$overview->status = '<span class="red">Issue</span>';
			}else if($overview->status == 'B'){
				$overview->status = '<span class="red">Bug</span>';
			}else if($overview->status == 'R'){
				$overview->status = '<span class="red">Re Issue</span>';
			}else if($overview->status == 'C'){
				$overview->status = '<span class="green">Completed</span>';
			}
        return $this->sendResponse($overview,'Overview detail list');
	}

	function whatever($array, $key, $val) {
		foreach ($array as $keys=>$item)
			if (isset($item[$key]) && $item[$key] == $val){
				return $keys + 1;
			}

		return false;
	}

    public function newtask($id){
        $task = Overview::where('added_by','=',$id)->where('status','=','N')->get();
       return response()->json($task);
    }

    public function issuetask($id){
        $issue = Overview::where('added_by','=',$id)->where('status','=','I')->get();
       return response()->json($issue);

    }

    public function reissuetask($id){
        $reissue = Overview::where('added_by','=',$id)->where('status','=','R')->get();
       return response()->json($reissue);

    }

    public function complete($id){
        $complete = Overview::where('added_by','=',$id)->where('status','=','C')->get();
       return response()->json($complete);
    }

    public function addoverview(Request $request) {
		$validator = Validator::make($request->all(), [
			'title' => 'required',
			'status' => 'required',
		]);

		if($validator->fails()){
			return $this->sendError('Validation Error.', $validator->errors());
		}

		$overviewcount = Overview::where('project_id',$request->idd)->count();
		$overviewid =  $overviewcount + 1;

		$pid = $this->encrypt($request->idd,'D');
		$project = Project::where('id',$pid)->first();
		$eid = explode(",",$project->assign_employee);
		$employee = Employee::whereIn('id',$eid)->get();

		$overview = new Overview;
		$overview->title = $request->title;
		$overview->status = $request->status;
        $overview->description = $request->description;
        $overview->project_id = $id = $this->encrypt($request->idd,'D');
        $overview->added_by = $request->iddd;
        $overview->added_type = $request->type;
        $overview->drag_drop_status = $request->defect;
        $overview->overview_id = $overviewid;

		$overview->save();

		foreach($employee as $emp)
		{
			$email = $emp->email;
			$firstname = $emp->first_name;
			$lastname = $emp->last_name;
			$data = [
				'subject' => 'New Task:'.$request->title,
				'name' => $firstname. ' ' .$lastname,
				'email' => $email,
				'url' => env('SITE_URL')
			];
			if (strpos($data['email'], 'calidig.com') !== false) {
				$data['email'] = $data['email'].'.test-google-a.com';
			}
			Mail::send('email/task', $data, function($message) use ($data) {
				$message->to($data['email'])
				->subject($data['subject']);
			$message->from('noreply@calidig.com','No Reply');
			});
		}
		return $this->sendResponse($overview,'overview list');
    }

   public function destroy($id)
   {
       $overview = Overview::find($id);
	   $overview->delete_status = 'Y';
       $overview->save();

       return response()->json($overview);
   }
   public function edit($id)
   {
	   $id = $this->encrypt($id,'D');
       $overview = Overview::find($id);


       if (is_null($overview)) {
           return $this->sendError('overview not found.');
       }
       return response()->json($overview);
   }

	public function document_overview(Request $request, $id){
		$data = [];
		$oldfile = $request->oldfiles;
		if($request->hasfile('file'))
        {
			$i = 0;
            foreach($request->file('file') as $file)
            {
				$filenameWithExt = $file->getClientOriginalName();
				$filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $name = $filename.$i.time().'.'.$file->extension();
                $file->move(public_path().'/files/', $name);
                $data[] = $name;
				$i++;
            }
        }
		$files = implode(",",$data);
		$overview = Overview::find($id);
		$overview->attachment = ((!empty($files))?$files:'').((!empty($oldfile))?",".$oldfile:'');
		$overview->save();
		return response()->json($overview);
    }

    public function update(Request $request, $id)
    {
       $id = $this->encrypt($id,'D');
        $input = $request->all();

        $validator = Validator::make($input, [
            'title' => 'required',
            // 'status' => 'required',
            'description' => 'required',
        ]);


        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }


        $overview = Overview::find($id);

        $overview->title = $request->title;
		$overview->status = $request->status;
        $overview->description = $request->description;
        $overview->drag_drop_status = $request->drag_drop_status;
		$overview->save();

        return response()->json($overview);
    }

	public function updatetab(Request $request){
		$status = $request->id;
		$content = explode("_",$request->content);
		$response = ProjectStatu::where('status',$status)->first();
		$overview = Overview::find($content[1]);
		$overview->drag_drop_status = $response->id;
		$overview->save();

		$overview_id = $overview->id;
		$project_id = $overview->project_id;
		$added_by = $overview->added_by;
		$status = $overview->status;
		$dragdropstatus = $overview->drag_drop_status;

		$log = new Log;
		$log->overview_id = $overview_id;
		$log->project_id = $project_id;
		$log->added_by = $added_by;
		$log->status = $status;
		$log->drag_drop_status = $dragdropstatus;
		$log->save();

		return $this->sendResponse('','Status Update');
	}

	public function addcomment(Request $request){
		$id = $this->encrypt($request->id,'D');
		$project_id = $this->encrypt($request->project_id,'D');
		$client_id = $request->client_id;
		$client_type = $request->type;


		$comment = Overview::find($id);
		$overview_id = $comment->id;
		$dragdropstatus = $comment->drag_drop_status;
		$title = $comment->title;
		$description = $comment->description;


		$addcomment = new Log;
		$addcomment->overview_id = $overview_id;
		$addcomment->project_id = $project_id;
		$addcomment->comment = $request->comment;
		$addcomment->added_by = $client_id;
		$addcomment->drag_drop_status = $dragdropstatus;
		$addcomment->save();


		$notification = new Notification;
		$notification->title = $title;
		$notification->description = substr($description, 0, 200);
		$notification->type = 'P';
		if($client_type=='employee')
		{
			$notification->employee_id = $client_id;
		}
		$notification->project_id = $project_id;
		if($client_type=='client')
		{
			$notification->client_id = $client_id;
		}
		$notification->save();
		return $this->sendResponse('','comment add');

	}

	public function commentlist($id){
		$id = $this->encrypt($id,'D');
		// $list = Log::where('overview_id',$id)->orderBy('id', 'DESC')->first();
		$list = Log::select('logs.*','overviews.title','overviews.description')->leftJoin('overviews','logs.overview_id','overviews.id')->where('logs.overview_id',$id)->get();
		return $this->sendResponse($list,'comment list');
	}

	public function notification($id){
		$type = \Request::get('type');
		$id = \Request::get('user_id');
		if($type=='user'){
			$notifications = Notification::where('description', '!=', 'null')->where('title', '!=', 'null')->where('delete_at',NULL)->orderBy('updated_at', 'desc')->take(10)->get();
		}else{
			$hr = Employee::where('id',$id)->first();
			$role = $hr->role;
			if($role=='1'){
				$notifications = Notification::where('description', '!=', 'null')->where('title', '!=', 'null')->orderBy('updated_at', 'desc')->take(10)->get();
			}else{
				$notifications = Notification::whereNull('employee_id')->orwhere('employee_id', 'LIKE', "%{$id}%")->orwhere('client_id', 'LIKE', "%{$id}%") ;
				$notifications = $notifications->where('description', '!=', 'null')->where('title', '!=', 'null')->orderBy('updated_at', 'desc')->take(10)->get();
			}
		}
        return $this->sendResponse($notifications,'Notification list');
	}

	public function all_notification($id){
		$type = \Request::get('type');
        $id = \Request::get('user_id');

		if($type=='user'){
			$notifications = Notification::where('description', '!=', 'null')->where('title', '!=', 'null')->where('delete_at',NULL)->orderBy('updated_at', 'desc')->get();
		}else{
			$hr = Employee::where('id',$id)->first();
			$role = $hr->role;
			if($role=='1'){
				$notifications = Notification::where('description', '!=', 'null')->where('title', '!=', 'null')->orderBy('updated_at', 'desc')->get();
			}else{
				$notifications = Notification::whereNull('employee_id')->orwhere('employee_id', 'LIKE', "%{$id}%")->orwhere('client_id', 'LIKE', "%{$id}%") ;
				$notifications = $notifications->where('description', '!=', 'null')->where('title', '!=', 'null')->orderBy('updated_at', 'desc')->take(6)->get();
			}
		}
		return $this->sendResponse($notifications,'View All Notifications');
	}

	public function deletenotification($id)
    {
		$notifications = Notification::find($id);
		$notifications->delete_at = '1';
		$notifications->save();

        return response()->json($notifications);
	}

	public function notificationstatus(Request $request){
		$type = \Request::get('type');
		$user_id = \Request::get('user_id');

		$status = $request->status;
		if(!empty($status)){
			if($type=='user'){
				$notification = Notification::where('admin_status',NULL)->get();
				foreach($notification as $noti){
					$noti->admin_status = $status;
					$noti->save();
				}
			}else{
				$notification = Notification::where('employee_status',NULL)->where('employee_id',$user_id)->get();
				foreach($notification as $noti){
					$noti->employee_status = $status;
					$noti->save();
				}
			}
			// $notification = Notification::where('status',NULL)->get();

		}else{
			if($type=='user'){
				$notification = Notification::where('admin_status',NULL)->count();
			}else{
				$notification = Notification::where('employee_status',NULL)->where('employee_id',$user_id)->count();
			}
		}

        return response()->json($notification);
	}
	public function uploadImage(Request $request){
		$request->validate([
            'upload' => 'required|mimes:jpeg,png,jpg,gif,svg,doc,docx,xls,xlsx,pdf,csv|max:12048',
        ]);

        $imageName = time().'.'.$request->upload->extension();

        $request->upload->move(public_path('ckeditorimages'), $imageName);

		$response = array('uploaded'=>1,'fileName'=>$imageName,"url"=>env('APP_URL').'ckeditorimages/'.$imageName);
		return response()->json($response);
	}

	public function defectlist() {
		$response = ProjectStatu::get();
		return $this->sendResponse($response,'Notification list');
	}

    public function listAllDefects(Request $request){
       $user_id = \Request::get('user_id');
	   $type = \Request::get('type');
	   $search = $request->search;
	   $pagevalue = $request->pagevalue;

       if($type == 'client') {
            $response = ProjectStatu::
            select('overviews.*','project_status.status as defect_status','projects.project_name')
            ->leftjoin('overviews','project_status.id','overviews.drag_drop_status')
            ->leftJoin('projects','overviews.project_id','projects.id', 'AND','overviews.client_id', $user_id)
            ->where('overviews.id', '!=', null)
			->orderBy('updated_at', 'DESC');
		if(!empty($search)){
			$response = $response->where('projects.project_name', 'LIKE', "%{$search}%")
								->orWhere('overviews.title', 'LIKE', "%{$search}%");
		}
		if(!empty($pagevalue)){
			$response = $response->paginate($pagevalue);
		}else{
			$response = $response->paginate(20);
		}
            foreach($response as $data) {
                $data->en_id = $this->encrypt($data->id);
            }
       }
       if($type == 'employee') {
            $assign_employees = [];
            $working_projects = [];
            $projects = Project::get();
            if($projects != null) {
                foreach($projects as $project) {
                    $assign_employees = explode(",",$project->assign_employee);
                    if($assign_employees != null) {
                        foreach($assign_employees as $emp) {
                            if($emp == $user_id) {
                                array_push($working_projects, $project->id);
                            }else {
                                array_push($working_projects, null);
                            }
                        }
                    }
                }
            }
            if($working_projects != null) {
                foreach($working_projects as $pro) {
                    $response = ProjectStatu::
                    select('overviews.*','project_status.status as defect_status','projects.project_name')
                    ->leftjoin('overviews','project_status.id','overviews.drag_drop_status')
                    ->leftJoin('projects','overviews.project_id', 'projects.id')
                    ->where('overviews.id', '!=', null)
					->orderBy('updated_at', 'DESC');
					if(!empty($search)){
						$response = $response->where('projects.project_name', 'LIKE', "%{$search}%")
											->orWhere('overviews.title', 'LIKE', "%{$search}%");
					}
					if(!empty($pagevalue)){
						$response = $response->paginate($pagevalue);
					}else{
						$response = $response->paginate(20);
					}
                    foreach($response as $data) {
                        $data->en_id = $this->encrypt($data->id);
                    }
                }
            }else {
                $response = "no project assigned yet!";
            }
       }

       return $this->sendResponse($response,'defact list');
    }

    public function listRespectiveProjects(){
        $user_id = \Request::get('user_id');
        $type = \Request::get('type');
        if($type == 'client') {
            $response = Project::where('client_id', $user_id)
            ->paginate(20);
        }

        if($type == 'employee') {
            $assign_employees = [];
            $projects = Project::get();
            if(!empty($projects)) {
                foreach($projects as $project) {
                    $assign_employees = explode(",",$project->assign_employee);
                    if(!empty($assign_employees)) {
                        foreach($assign_employees as $emp) {
                            if(!empty($emp)) {
                                if($emp == $user_id) {
                                    $response = Project::where('id', $project->id)->paginate(20);
                                }
                            }else {
                                $response = "no project has been assigned to you";
                            }
                        }
                    } else {
                        $response = "no project has been assigned to you";
                    }
                }
            }
            else {
                $response = "no project has been assigned to you";
            }
        }
        return $response;
       return $this->sendResponse($response,'projects list');
    }

    public function addDefect(Request $request) {
		$validator = Validator::make($request->all(), [
			'title' => 'required',
			// 'status' => 'required',
		]);

		if($validator->fails()){
			return $this->sendError('Validation Error.', $validator->errors());
		}

		$overviewcount = Overview::where('project_id',$request->project_id)->count();
		$overviewid =  $overviewcount + 1;

		$pid = $request->project_id;
		$project = Project::where('id',$pid)->first();
		$eid = explode(",",$project->assign_employee);
		$employee = Employee::whereIn('id',$eid)->get();
		$overview = new Overview;
		$overview->title = $request->title;
		$overview->status = $request->status;
        $overview->description = $request->description;
        $overview->project_id = $request->project_id;
        $overview->added_by = $request->iddd;
        $overview->added_type = $request->type;
        $overview->drag_drop_status = 1;
        $overview->overview_id = $overviewid;

		$overview->save();

		// foreach($employee as $emp)
		// {
		// 	$email = $emp->email;
		// 	$firstname = $emp->first_name;
		// 	$lastname = $emp->last_name;
		// 	$data = [
		// 		'subject' => 'Task/ Defect Notification:'.$request->title,
		// 		'name' => $firstname. ' ' .$lastname,
		// 		'email' => $email,
		// 		'url' => env('SITE_URL')
		// 	];

		// 	Mail::send('email/task', $data, function($message) use ($data) {
		// 		$message->to($data['email'])
		// 		->subject($data['subject']);
		// 	});
		// }
		return $this->sendResponse($overview,'overview list');
   }

    //    ----------------------------------------
    // send notification to respective employee/client if any changes in defect

    public function notifyDefectChanges(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'type' => 'required',
            'project_id' => 'required',
            'defect_id'=> 'required'
          ]);

          if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $project = Project::where('id', $request->project_id)->first();
        $eid = explode(",",$project->assign_employee);
		$notification = new Notification;

		$notification->title = $request->title;
		$notification->description = $request->description;
		$notification->type = $request->type;
		$notification->employee_id = implode(",",$eid);
		$notification->client_id = $project->client_id;
		$notification->project_id = $request->project_id;
        $notification->defect_id = $request->defect_id;

		$notification->save();

		return $this->sendResponse($notification,'Defect Notification sent!');

    }

    public function updateDefectNotification(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input,[
            'defect_id' => 'required',
            'title' => 'required',
            'description' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $notification = Notification::where('defect_id', $request->defect_id);

        $notification->title = $request->title;
		$notification->description = $request->description;
		$notification->type = $request->type;
        return response()->json($notification);
    }

}
