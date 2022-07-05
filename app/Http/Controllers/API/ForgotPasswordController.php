<?php 

namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Str;
use DB;
use Mail;
use Carbon\Carbon; 
use App\Models\User; 

use App\Models\Employee;

use App\Models\Client;

  

class ForgotPasswordController extends Controller

{

      /**

       * Write code on Method

       *

       * @return response()

       */

      public function showForgetPasswordForm()

      {

         return view('auth.forgetPassword');

      }
      
      
      public function clearRoute()
		{
			
			\Artisan::call('route:clear');
		}  

  

      /**

       * Write code on Method

       *

       * @return response()

       */

      public function submitForgetPasswordForm(Request $request)

      {
		  
		  
	if ($request->type == "user") {
		
		
		$validator = Validator::make($request->all(), [ 
             'email' => 'required|email|exists:users',
              'type' => 'required',
        ]);


          
	  }elseif ($request->type == "employee") {
		  

          
         $validator = Validator::make($request->all(), [ 
              'email' => 'required|email|exists:employees',
              'type' => 'required',
        ]);
		  
	  }elseif ($request->type == "client") {
		  
		  
		 $validator = Validator::make($request->all(), [ 
            'email' => 'required|email|exists:clients',
              'type' => 'required',

        ]);
		  
		  
	  }
          
       if($validator->fails()){
           return response()->json(['status' => 0, 'msg' => 'Invalid email!']);
       } 
       
       


          $token = Str::random(64);

  

          DB::table('password_resets')->insert([

              'email' => $request->email,
              
              'type' => $request->type,  

              'token' => $token, 

              'created_at' => Carbon::now()

            ]);
            
            
            return response()->json(['token' => $token, 'msg' => 'The token is generated successfully.']);

		/*
          Mail::send('email.forgetPassword', ['token' => $token], function($message) use($request){

              $message->to($request->email);

              $message->subject('Reset Password');

          });

  

          return back()->with('message', 'We have e-mailed your password reset link!');
          
          */

      }

      /**

       * Write code on Method

       *

       * @return response()

       */

      public function showResetPasswordForm($token) { 

         return view('auth.forgetPasswordLink', ['token' => $token]);

      }

  

      /**

       * Write code on Method

       *

       * @return response()

       */

      public function submitResetPasswordForm(Request $request)

      {
		  
	
		

          $request->validate([

              'email' => 'required|email',
              
              'type' => 'required',

              'password' => 'required|string|min:6|confirmed',

              'password_confirmation' => 'required'

          ]);



          $updatePassword = DB::table('password_resets')

                              ->where([

                                'email' => $request->email,
                                
                                'type' => $request->type,

                                'token' => $request->token

                              ])->first();

  

          if(!$updatePassword){
			  
		

            return response()->json(['status' => 0, 'msg' => 'Invalid token!']);

          }
		
		
		if ($request->type == "user") {
			
			
			
		          $user = User::where('email', $request->email)

                      ->update(['password' => md5($request->password)]);

 

          DB::table('password_resets')->where(['email'=> $request->email,'type' => $request->type])->delete();

  
			
			return response()->json(['status' => 1, 'msg' => 'Your password has been changed!']);
        
		
		
		} elseif ($request->type == "employee") {
			
			
			
		          $user = Employee::where('email', $request->email)

                      ->update(['password' => md5($request->password)]);

 

          DB::table('password_resets')->where(['email'=> $request->email,'type' => $request->type])->delete();

  

        return response()->json(['status' => 1, 'msg' => 'Your password has been changed!']);
		
		
		} elseif ($request->type == "client") {
			
		          $user = Client::where('email', $request->email)

                      ->update(['password' => md5($request->password)]);

 

          DB::table('password_resets')->where(['email'=> $request->email,'type' => $request->type])->delete();

  

        return response()->json(['status' => 1, 'msg' => 'Your password has been changed!']);
		
		
		} else {
	

		
		return response()->json(['status' => 0, 'msg' => 'Invalid user!']);
		
		}
		
  



      }

}
