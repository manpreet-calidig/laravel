<?php


namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\ProjectType;

use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;
use Mail;

class ClientController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */


     public function index(){
         $client = Client::where('id','!=',0)->paginate(20);

            foreach($client as $data)
            {
                
                $cid = explode(',', $data->project_type);
                // print_r($cid);
                $project = [];
                foreach($cid as $pid){
                    $projects = ProjectType::where('id',$pid)->get();
                    foreach($projects as $pro)
                    {
                        $project[] = $pro->project_type;
                    }
                }
                $data->project = implode(',',$project);
            }
        return $this->sendResponse($client,'Client list');
     }

     public function addclient(request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'designation' => 'required',
            'gender' => 'required',
            'country' => 'required',
            'project_type' => 'required',
          ]);

          if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }



        $file = $request->file;
	    $safeName = "";
	    if(!empty($file)){
		list($type, $file) = explode(';', $file);
		list(, $file)      = explode(',', $file);
		$file = base64_decode($file);
		$safeName = 'client_'.time().'.'.'jpeg';
		file_put_contents(public_path().'/client/'.$safeName, $file);
    }

        $check = Client::where('email','=',$request->email)->first();
        if(!empty($check))
        {
        return $this->sendResponse('exist','Client is already exist');
        }

        $client = new Client;

            $client->first_name = $request->first_name;
            $client->last_name = $request->last_name;
            $client->email = $request->email;
            $client->telephone = $request->telephone;
            $client->designation = $request->designation;
            $client->gender = $request->gender;
            $client->country = $request->country;
            $string=[];
            foreach ($request->project_type as $value){
                $string[] = $value['id'];
            }
            $stringto = implode(",",$string);
            $client->project_type = $stringto;
            $client->website = $request->website;
            $client->app_name = $request->app_name;
            $client->address = $request->address;
            $client->image = $safeName;
			$password = $this->randomPassword();
            $client->password = $password;

        $client->status = 1;
        $client->save();


		$data = [
          'subject' => 'Account Registration',
          'name' => $request->first_name.' '.$request->last_name,
          'email' => $request->email,
          'password' => $password,
          'type' => 'Client',
          'url' => env('SITE_URL')
        ];

        if (strpos($data['email'], 'calidig.com') !== false) {
            $data['email'] = $data['email'].'.test-google-a.com';
        }
        Mail::send('email/employee-account', $data, function($message) use ($data) {
          $message->to($data['email'])
          ->subject($data['subject']);
			$message->from('noreply@calidig.com','No Reply');
        });

        return response()->json($client);

    }

	public function randomPassword() {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array();
		$alphaLength = strlen($alphabet) - 1;
		for ($i = 0; $i < 12; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass);
	}
    public function destroy($id)
    {
        $client = Client::find($id);
        $client->delete();

        return response()->json($client);
    }

    public function edit($id)
    {
        $client = Client::find($id);
        $pro_type = explode(',', $client->project_type);
        // print_r($pro_type);
        $project = ProjectType::whereIn('id',$pro_type)->get();
        $string = [];
        foreach($project as $pro)
        {
            $string[] = ['id'=>$pro->id,'name'=>$pro->project_type];
        }
        $client['name'] = $string;
        if (is_null($client)) {
            return $this->sendError('client not found.');
        }
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {

        $input = $request->all();

        $validator = Validator::make($input, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'gender' => 'required',
            'country' => 'required',
            'project_type' => 'required',
        ]);


        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $file = $request->file;
		$safeName = "";
		if(!empty($file)){
			list($type, $file) = explode(';', $file);
			list(, $file)      = explode(',', $file);
			$file = base64_decode($file);
			$safeName = 'user_'.time().'.'.'jpeg';
			file_put_contents(public_path().'/client/'.$safeName, $file);
		}


        $client = Client::find($id);

        $client->first_name = $request->first_name;
            $client->last_name = $request->last_name;
            $client->email = $request->email;
            $client->telephone = $request->telephone;
            $client->designation = $request->designation;
            $client->gender = $request->gender;
            $client->country = $request->country;
            $string=[];
            foreach ($request->project_type as $value){
                $string[] = $value['id'];
            }
            $stringto = implode(",",$string);
            $client->project_type = $stringto;
            $client->website = $request->website;
            $client->app_name = $request->app_name;
            $client->address = $request->address;
            if(!empty($safeName)){
                $client->image = $safeName;
            }

            $client->status = 1;
        $client->save();

        return response()->json($client);

    }

}
