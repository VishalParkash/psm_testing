<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Share;
use App\Client;
use App\Profile;
use App\Portfolio;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\CommonTrait;
use Carbon\Carbon;

class ClientController extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   use CommonTrait;
//    public function __construct()
// {
//     $this->middleware('auth');
//     $this->middleware(function ($request, $next) {
//     $this->id = Auth::user()->id;
//     $this->email = Auth::user()->email;
//         $loggedInUserId = ($this->id);
//         $loggedInUserEmail = ($this->email);

//         $User = User::find($loggedInUserId);
//         if(empty($User)){
//             $status = false;
//             $response['message'] = 'Unauthenticated.';
//             return response()->json($response);

//             // if($User->user_role != 'client' ){
//             //     $status = false;
//             //     $response['message'] = 'Unauthenticated.';
//             //     return response()->json($response);
//             // }
//         }
//         return $next($request);
//     });
// }

    public function index()
    {
        //
    }

    public function login(Request $request, $queryString){
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        if(empty($queryString)){
            $response['status'] = false;
            $response['message'] = "Invalid login Url.";
            return $response;
        }
        if(!empty($userRequest)){

            $getShare = Share::where('queryString','=', $queryString)->first();
            if(!empty($getShare)){
                if($user = User::where('email', '=', $userRequest->clientEmail)->first()){
                    $response['status'] =  true;
                    
                }else{
                    $response['status'] = false;
                    $response['message'] = "We couldn't find that email in our records. Please contact the administrator.";
                    return $response;
                }
                $getClient = Client::where('share_id', '=', $getShare->id)
                                        ->where('clientEmail', '=', $userRequest->clientEmail)
                                        ->first();
                if(empty($getClient)){
                    $response['status'] = false;
                    $response['message'] = "We couldn't find a user associated with this share. Please contact administrator.";
                    return $response;
                }


                // $getClient = Client::find($client_id)->toArray();
                if(!empty($getClient)){
                    $getClient->clientName = $userRequest->clientName;
                    $getClient->save();
                    $getClient = $getClient->toArray();
                    // $getShare = Share::find($getClient['share_id'])->toArray();
                    $getClient['share_title'] = $this->getShareTitle($getClient['share_id']);
                    $getPortfolios = $getShare->profileShared;
                    $Portfolio_ids = explode(',', $getPortfolios);
                    if(!empty($Portfolio_ids)){
                        foreach($Portfolio_ids as $Portfolio_id){
                            $getPortfolio = Portfolio::find($Portfolio_id);
                            if(!empty($getPortfolio)){
                                $Portfolio['resource_name']= $this->getResourceName($getPortfolio->profile_id)['resource_name'];
                                $Portfolio['url']= $getPortfolio->portfolio_url;
                                $Portfolio['profile_title']= $getPortfolio->profile_title;
                                $Portfolio['id']= $getPortfolio->id;
                            }
                            $ProfileArr[] = $Portfolio;
                        }
                        $getShare->lastViewedOn = Carbon::now();
                        $getShare->save();
                    }
                    $getClient['profiles'] = $ProfileArr;
                    $response['status'] = true;
                    $response['token'] =  $user->createToken('ProfileSharingApp-client')->accessToken; 
                    $response['result'] = $getClient;
                    $response['message'] = "valid result";
                }

                
            }else{
                $response['status'] = false;
                $response['message'] = 'Invalid url';
            }
        }else{
            $response['status'] = false;
            $response['message'] = 'Invalid inputs';
        }

        return $response;

    }

    
    public function profiles1(Request $request){
        $LoggedInClient = $request->user()->toArray();
        $client_id = $LoggedInClient['id'];


        $getClient = Client::find($client_id)->toArray();
        if(!empty($getClient)){
            $getShare = Share::find($getClient['share_id'])->toArray();
            $getClient['share'] = $getShare;
            $getPortfolios = $getShare['profileShared'];
            $Portfolio_ids = explode(',', $getPortfolios);
            if(!empty($Portfolio_ids)){
                foreach($Portfolio_ids as $Portfolio_id){
                    $getPortfolio = Portfolio::find($Portfolio_id);
                    if(!empty($getPortfolio)){
                        $getProfile = Profile::find($getPortfolio->profile_id);
                        if(!empty($getProfile)){
                            $getPortfolio['profile'] = $getProfile;
                        }
                    }
                    $ProfileArr[] = $getPortfolio;
                }
            }
            $getClient['profiles'] = $ProfileArr;


            
            $response['status'] = true;
            $response['result'] = $getClient;
            $response['message'] = "valid result";
        }else{
            $response['status'] = false;
            $response['message'] = "No client found";
        }

        return $response;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function sharing(Request $request)
    {
        //

        // echo "<pre>";print_r($request->session()->get('userData'));die;
        // $QueryStr = Str::random(15);
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        // echo "<pre>";print_r($userRequest);die;
        
        $client_id = 5;
        $UrlToShare = 'https://millipixels.com/profiles/NC4SLMSKm3KVyRr';
        $UrlAccess = 'Public';


        // if ($request->session()->has('userData')) {
        //     $sessionData = $request->session()->get('userData');
        // }else{
        //     return;
        // }

        // $client_id = $sessionData['user'][0]['id'];
        // $UrlToShare = $sessionData['user'][0]['Url'];
        // $UrlAccess = $sessionData['user'][0]['UrlAccess'];


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

        // $UrlToShare = 'https://millipixels.com/profiles/'.$QueryStr;

        // $SharingUrl = url('/profiles/').$QueryStr;

        $ClientShare = new ClientShare([
            'client_id' => ($client_id),
            'clientGoogle_email' => ($userRequest->clientGoogle_email),
            'clientLinkedIn_userName' => $userRequest->clientLinkedIn_userName,
            'Url' => $UrlToShare,
            'UrlAccess' => $UrlAccess,
            'profileShared' => $userRequest->profileShared,
            'status' => $userRequest->status,
        ]);

        if($ClientShare->save()){

            $response['status'] = true;
            $response['message'] = 'URL Sharing Successful';
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


    public function create(Request $request)
    {
        //
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));
        
        $UrlToShare = 'https://millipixels.com/profiles/NC4SLMSKm3KVyRr';
        $UrlAccess = 'Public';

        $ClientShare = new ClientShare([
            'client_id' => ($userRequest->client_id),
            'clientGoogle_email' => ($userRequest->clientGoogle_email),
            // 'clientLinkedIn_userName' => $userRequest->clientLinkedIn_userName,
            'Url' => $UrlToShare,
            'UrlAccess' => $UrlAccess,
            'profileShared' => $userRequest->profileShared,
            'status' => $userRequest->status,
        ]);

        if($ClientShare->save()){

            $response['status'] = true;
            $response['message'] = 'URL Sharing Successful';
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
