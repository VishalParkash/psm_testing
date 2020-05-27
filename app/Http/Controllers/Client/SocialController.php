<?php

// namespace App\Http\Controllers\Auth;
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;

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

      public function redirectToProvider($SocialProvider, $callback=false)
    {
    	// die($callback);
    	if(!empty($callback)){
    		return $callback;
    	}
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
		// echo "<pre>";print_r($user);die;
		if($user){
		    // $response['status'] = true;
		    // $response['message'] = 'User already registered';
		    // $response['user'] = $userSocial;
		    	$response['status'] = true;
                $response['message'] = 'Login Successful.';
                $response['token'] = $user->createToken('Personal Access Token')->accessToken;
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
                $response['token'] = $user->createToken('Personal Access Token')->accessToken;
                $response['user'] = $user;
                $this->redirectToProvider('linkedin', $response);
			}else{
				$response['status'] = false;
				$response['message'] = 'error occured';
			}
			
			
		    
		}
	        // $response['status'] = true;
	        // $response['user'] = $userSocial;

	        return $response;
    }

    public function linkedinAuth(){
    	$authorizationUrl = "https://www.linkedin.com/oauth/v2/authorization?";
    	$redirect_uri = "https://dev.evantiv.com/mp_share/api/client/social/access";
		$client_id = "81h9vzmrcm0o5z";
		$client_secret = "Q3lLm5AAKpvgxmwH";
    	$response_type = 'code';
		$scope ="r_liteprofile%20r_emailaddress%20w_member_social";

		$curl = curl_init();
	
		$post_data= "response_type=".$response_type."&client_id=".$client_id."&redirect_uri=".$redirect_uri."&scope=".$scope;
		echo $Url = $authorizationUrl.$post_data;
		// file_get_contents($Url);
		// die('stop');
		// curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($curl, CURLOPT_URL, $Url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            ));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		if(!$result){die("Connection Failure");}
		curl_close($curl);
		echo "<pre>"; print_r($result);

		die;
    }
    public function linkedinAccess(){
    	$AccesstokenUrl = "https://www.linkedin.com/oauth/v2/accessToken";
    	$redirect_uri = "https://dev.evantiv.com/mp_share/api/client/social/access";
		$client_id = "81h9vzmrcm0o5z";
		$client_secret = "Q3lLm5AAKpvgxmwH";
    	$response_type = 'code';
		
		// echo "<pre>";print_r($_REQUEST['code']);die;
		if(!empty($_REQUEST['code'])){
			$code = $_REQUEST['code'];
		}else{
			$response['status'] = false;
			$response['message'] = 'Invalid code';
			return $response;
		}
		$params = array(
			'client_id' 		=> $client_id,
			'client_secret' 	=> $client_secret,
			'grant_type' 		=> 'authorization_code',
			'redirect_uri' 		=> $redirect_uri,
			'code' 		=> $_GET['code']
		);

        // Access Token request
        $url = 'https://www.linkedin.com/oauth/v2/accessToken?' . http_build_query($params);
        $postdata = (http_build_query($params));
        // Tell streams to make a POST request
        $context = stream_context_create(
                array('https' =>
                    array('method' => 'POST',
                    	  'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    	  // 'content' => $postdata
                    )
                )
        );
        // $ctx = stream_context_create($params);

        // $context  = stream_context_create($cairo_font_options_status(options));
        // Retrieve access token information
        $response = file_get_contents($url, false, $context);
        $token = json_decode($response);
        return $token->access_token;


		$post_data="grant_type=authorization_code&client_id=".$client_id."&client_secret=".$client_secret."&redirect_uri=".$redirect_uri."&code=".$code;
		$headers = array();
		$headers[] = "Content-Type: application/x-www-form-urlencoded";
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_POST, 1);
		// curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($curl, CURLOPT_URL, $AccesstokenUrl);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		echo "<pre>"; var_dump($result);die;
		if(!$result){die("Connection Failure");}
		curl_close($curl);
		echo "<pre>"; print_r($result);

		die;


    	$curl = curl_init();
		
		$post_data="grant_type=authorization_code&client_id=8636c4rhjwmh4j&client_secret=CCTbt6bOop9vYKMv&redirect_uri=http://localhost:3001/&code=".$code;
		// curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($curl, CURLOPT_URL, 'https://www.linkedin.com/oauth/v2/accessToken');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            ));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$result = curl_exec($curl);
		if(!$result){die("Connection Failure");}
		curl_close($curl);
		echo $result;

    	die;
    	$endpoint = "https://www.linkedin.com/oauth/v2/accessToken";
		$client = new \GuzzleHttp\Client();
		// $code = $;
		//https://www.linkedin.com/uas/oauth2/accessToken?grant_type=client_credentials&code=AQTksKxt1frc6MW5_f16f07U5TjVWadfQIJ8Yc2pDQhbPD38p0oTD_Bvl539qOrnpyKhku7jd8tGa-55dV7p0XVg79kOQFmDTYP3hcYFQWaekyoTiiO5gJPmT8OfwfwkRDgIteKoF308325ea2iRZolpWNo70CyyQqcIhaUI7-wMlMB2YVShw0v8ldWcXw&client_id=81h9vzmrcm0o5z&redirect_uri=https://dev.evantiv.com/mp_share/api/social/linkedin/callback&client_secret=Q3lLm5AAKpvgxmwH
		// $code ="AQT9wN84IAOW26CR_vrHGa_2lDoMExirxD2HtYWrobV9HELNZ7ptx3yzshmUE9YQrHrEfFAEkhvlVKoZ0GKc0zjUTM2PZ35_1vYeA7Nszf9GDunF3JqYNRlr1TKfQQcewZ2-_iz2-zI6dMGGUjvQ07G76tjJg1rCa4Dt8WDwLx2An02kkVt6rn8CQyzHuw";
		$grant_type = "authorization_code";
		$redirect_uri = "http://localhost:3001/";
		$client_secret = "CCTbt6bOop9vYKMv";
		$client_id = "8636c4rhjwmh4j";

		$result = $client->request('POST', $endpoint, [
			     'headers'  => ['content-type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'],
			'query' => [
		    'grant_type' => $grant_type, 
		    'code' => $code, 
		    'redirect_uri' => $redirect_uri, 
		    'client_secret' => $client_secret, 
		    'client_id' => $client_id, 
		]]);

		// url will be: http://my.domain.com/test.php?key1=5&key2=ABC;

		$statusCode = $result->getStatusCode();
		$content = $result->getBody();

		// $response['result'] =$content;
		$response['result'] =$content;
		echo "<pre>";print_r($content);
		return $response;
		
    }
}
