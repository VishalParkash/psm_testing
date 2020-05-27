<?php

// namespace App\Http\Controllers\Auth;
namespace App\Http\Controllers;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Socialite;
use Carbon\Carbon;
// use Illuminate\Support\Str;?
// use Throwable;


class SocialController extends Controller
{

    // public function google(Request $request){

    // }

      public function redirectToProvider($SocialProvider)
    {
    	// die($SocialProvider);
    	try {
                return Socialite::driver($SocialProvider)->redirect();
                
            } catch (Exception $ex) {
                // Debug via $ex->getMessage();
                $response['status'] = false;
                $response['message'] = $ex->getMessage();
                return $response;
            }
            
    	
    	// try{
    	// 	Socialite::driver('google')->redirect();
    	// }
    	// catch(Throwable as ){}
       	
     //   	die('here');
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($SocialProvider)
    {
        // $user = Socialite::driver('google')->user();
		
		if($SocialProvider=='twitter'){
			$userSocial =   Socialite::driver($SocialProvider)->user();
		}else{
			$userSocial =   Socialite::driver($SocialProvider)->stateless()->user();
		}
		$user     =   User::where(['email' => $userSocial->getEmail()])->first();
		echo "<pre>";print_r($user);die;
		if($user){
		    // $response['status'] = true;
		    // $response['message'] = 'User already registered';
		    // $response['user'] = $userSocial;
		    	$response['status'] = true;
                $response['message'] = 'Login Successful.';
                $response['token'] = $user->createToken('Personal Access Token')->plainTextToken;
                $response['user'] = $user;
		}else{
			if($user = User::create([
		                'name'          => $userSocial->getName(),
		                'email'         => $userSocial->getEmail(),
		                'image'         => $userSocial->getAvatar(),
		                'provider_id'   => $userSocial->getId(),
		                'provider'      => $SocialProvider,
		                // 'email_verification_token' => Str::random(60)
		                'last_login' => Carbon::now(),
		                'email_verified' => 1
		            ])){
				// $response['message'] = 'User registered successfully';
				// $response['status'] = true;
				// $response['user'] = $userSocial;

				$response['status'] = true;
                $response['message'] = 'Login Successful.';
                $response['token'] = $user->createToken('Personal Access Token')->plainTextToken;
                $response['user'] = $user;
			}else{
				$response['status'] = false;
				$response['message'] = 'error occured';
			}
			
			
		    
		}
	        // $response['status'] = true;
	        // $response['user'] = $userSocial;

	        return $response;
    }

//     public function TwitterCallback()
// {
//          $twitterSocial =   Socialite::driver('twitter')->user();
//         $users       =   User::where(['email' => $twitterSocial->getEmail()])->first();
// if($users){
//             Auth::login($users);
//             return redirect('/home');
//         }else{
// $user = User::firstOrCreate([
//                 'name'          => $twitterSocial->getName(),
//                 'email'         => $twitterSocial->getEmail(),
//                 'image'         => $twitterSocial->getAvatar(),
//                 'provider_id'   => $twitterSocial->getId(),
//                 'provider'      => 'twitter',
//             ]);
//             return redirect()->route('home');
//         }
//   }
}
