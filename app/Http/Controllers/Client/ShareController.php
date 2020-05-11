<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ClientShare;

class ShareController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function info(Request $request){
        // if ($request->session()->has('userData')) {
        //     $response['sessionData'] = $request->session()->get('userData');
        //     return view('client.welcome', $response);
        // }
    }
    public function share(Request $request)
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


    public function create()
    {
        //
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
