<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\User; 
use Illuminate\Support\Facades\Auth;
use Validator;

class AdminController extends Controller
{
    public function register(Request $request){
        $validator = Validator::make($request->all(), [ 
            // 'name' => 'required', 
            'email' => 'required|email', 
            // 'password' => 'required', 
            // 'c_password' => 'required|same:password', 
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $input = $request->all();
        // $input['password'] = bcrypt($input['password']); 
        $rand_password = Str::random(5);
        // $password = bcrypt($rand_password);
        $input['password'] = bcrypt($rand_password);
        $Admin = User::create($input); 
        // $success['token'] =  $Admin->createToken('MyApp')->accessToken; 
        $success['name'] =  $Admin;
        return response()->json(['result'=>$success, 'status' => true]); 
    }

    public function login(Request $request){
        // if(Auth::guard('admin')->attempt(['email' => request('email')])){
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

         // echo "<pre>";print_r($userRequest);die;
        $input = $request->all();
        // echo "<pre>";print_r($input);die;
        $validator = Validator::make($request->all(), [ 
            // 'name' => 'required', 
            'email' => 'required|email', 
            // 'password' => 'required', 
            // 'c_password' => 'required|same:password', 
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        
        if($user = User::where('email', '=', $input['email'])->first()){
            
            $success['status'] =  true;
            $success['user'] =  $user; 
            $success['token'] = $user->createToken('ProfileSharingApp-admin')->accessToken; 
            // $success['expires_at'] = Carbon::parse($tokenResult->token->expires_at)->toDateTimeString();
            return response()->json($success); 
        } 
        else{
            
            // $input['password'] = bcrypt($input['password']); 
            $rand_password = Str::random(5);
            // $password = bcrypt($rand_password);
            $input['password'] = bcrypt($rand_password);
            $Admin = User::create($input); 
            // $success['token'] =  $Admin->createToken('MyApp')->accessToken; 
            $success['status'] =  true;
            $success['user'] =  $Admin;
            $success['token'] =  $Admin->createToken('ProfileSharingApp-admin')->accessToken;
            return response()->json($success);
        }
        $success['status'] = false;
        $success['message'] = 'Invalid Login';
        return response()->json($success);

    }
    
}
