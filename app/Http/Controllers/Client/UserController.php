<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\CreateShare;
use Socialite;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    protected $redirectTo = '/welcome';


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function redirectToProvider($SocialProvider)
    {
        // return Socialite::driver('github')->redirect();
        // echo $SocialProvider;die;
        try {
                return Socialite::driver($SocialProvider)->redirect();
                
            } catch (Exception $ex) {
                // Debug via $ex->getMessage();
                $response['status'] = false;
                $response['message'] = $ex->getMessage();
                return $response;
            }
    }

    /**
     * Obtain the user information from social netwrork.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Request $request, $SocialProvider)
    {
        $userSocial = Socialite::driver($SocialProvider)->stateless()->user();

        $user = User::where('email', '=', $userSocial->getEmail())
                    ->where('userType', '=', 'client')
                    ->first();

                            /*
                            $user->getId();
                            $user->getNickname();
                            $user->getName();
                            $user->getEmail();
                            $user->getAvatar();*/


        // where(['clientGoogle_email' => $userSocial->getEmail()])->first();
        // ->orWhere('name', 'John')
        // $response['user'] = $userSocial;
        // return $response;
        // echo "here";
        // echo "<pre>";print_r($userSocial);
        // die;
        if($user){
            $response['status'] = true;
                $response['message'] = 'Login Successful.';
                $response['token'] = $user->createToken('ProfileSharingApp-client')->accessToken;
                $response['user'] = $user->toArray();
                $response['socialData'] = $userSocial;
                $response['name'] = $userSocial->getName();                
        }else{
            $response['status'] =false;
            $response['message'] ='Sorry! You cannot login to the system';
            
        }
            return $response;

        // $user->token;
    }


    public function index()
    {
        //
        return view('client.home');
    }

    public function welcome(Request $request)
    {
        //
        if ($request->session()->has('userData')) {
            $response['sessionData'] = $request->session()->get('userData');
            return view('client.welcome', $response);
        }
        return redirect('/');
        
        // echo "<pre>";print_r($sessionData['user']['clientName']);die;
        
    }

    public function errors(Request $request)
    {
        //
        if ($request->session()->has('loginError')) {
            $response['sessionData'] = $request->session()->get('loginError');
            return view('client.errors', $response);
        }
        return redirect('/');
        
        // echo "<pre>";print_r($sessionData['user']['clientName']);die;
        
    }

    public function logout(Request $request)
    {
          $request->session()->flush();

        return redirect('/');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function client(Request $request)
    {
        //
        $QueryStr = Str::random(15);
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $createdBy = "Admin";
        $updatedBy = "Admin";

        // echo "<pre>";print_r($userRequest);die;

        // $validator = Validator::make($userRequestValidate, [
        //     'email' => 'required|string|email|unique:users',
        //     'password' => 'required',
        //     'user_role'  => 'required',
        //     // 'mobile_number' => 'unique:users,mobile_number,NULL',
        //     // 'title'  => 'required',
        //     // 'portFolio_Url'  => 'required',
        // ]);

        // if($validator->fails()){
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Validation Error',
        //         'error' => $validator->errors()
        //     ]);      
        // }

        $UrlToShare = 'https://millipixels.com/profiles/'.$QueryStr;

        // $SharingUrl = url('/profiles/').$QueryStr;
        $ClientShare = new ClientShare([
            'client_id' => ($userRequest->client_id),
            'clientGoogle_email' => ($userRequest->clientGoogle_email),
            'clientLinkedIn_userName' => $userRequest->clientLinkedIn_userName,
            'Url' => $UrlToShare,
            'UrlAccess' => $userRequest->UrlAccess,
            'profileShared' => $userRequest->profileShared,
            'status' => $userRequest->status,
        ]);

        if($ClientShare->save()){

            $response['status'] = true;
            $response['message'] = 'URL Sharing Successfully';
            $response['ClientShare'] = $ClientShare;


            // $SharingUrl = new SharingUrl;

            // if($SharingUrl){
            //     $SharingUrl->sharing_id = $CreateShare->id;
            //     $SharingUrl->queryString = $UrlToShare;
            //     $SharingUrl->profileShared = $userRequest->profileShared;
            //     $SharingUrl->urlSharingAccess = $userRequest->UrlAccess;

            //     if($SharingUrl->save()){
            //         $response['status'] = true;
            //         $response['message'] = 'Sharing Url generated Successfully';
            //         $response['user'] = $CreateShare;
            //     }else{
            //         $response['status'] = false;
            //         $response['message'] = 'Error occurred';
            //     }

            // }
            // die('here');
            // try {
    // Mail::to($user->email, '$user->name')->send(new VerificationMail($user));
            
        }else{
            $response['status'] = false;
            $response['message'] = 'Error occurred';
        }

        return $response;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
