<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Profile;
use App\Portfolio;
use App\Assistance;
use Validator;
use Illuminate\Support\Facades\Storage;
use App\Http\Traits\CommonTrait;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Textract\TextractClient;
use Illuminate\Support\Facades\Mail;
use App\Mail\AssistanceMail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class UserController extends Controller {
    use CommonTrait;
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


        public function __construct1()
{
    $this->middleware('auth');
    $this->middleware(function ($request, $next) {
    $this->id = Auth::user()->id;
        $loggedInUserId = ($this->id);

        $User = User::find($loggedInUserId);
        if(!empty($User)){
            if($User->user_role != 'admin' ){
                $status = false;
                $response['message'] = 'Unauthenticated.';
                return response()->json($response);
            }
        }else{
            $status = false;
            $response['message'] = 'Unauthenticated.';
            return response()->json($response);
        }
        return $next($request);
    });
}


    public function __construct111(Request $request){

        $bearerToken=$request->bearerToken();
$tokenId= (new Parser())->parse($bearerToken)->getHeader('jti');
$client = Token::find($tokenId)->client;


        $loggedInUser = $request->user();
        echo "<pre>";print_r($client);
        die;
        if(empty($loggedInUser)){
            $response['status'] = false;
            $response['message'] = 'Unauthenticated.';
        }
        $loggedInUserId = $loggedInUser['id'];

        $User = User::find($loggedInUserId);
        if(!empty($User)){
            if($User->user_role != 'admin' ){
                $response['status'] = false;
                $response['message'] = 'Unauthenticated.';
            }
        }else{
            $response['status'] = false;
            $response['message'] = 'Unauthenticated.';
        }
        return $response;
        
    }

    public function list()
    {
        //
        $Profiles = Profile::all()->toArray();
        // echo "<pre>";print_r($Profiles);die;

        if($Profiles){

            foreach($Profiles as $profile){

                // echo "<pre>";print_r($profile->id);
                // echo "<br>next";
                $portfolioList = Portfolio::where('profile_id', '=', $profile['id'])->get()->toArray();
                // echo "<pre>";print_r($portfolioList);
                $portfolioArr = array();
                foreach($portfolioList as $portfolio){
                    $diff = Carbon::parse($portfolio['lastViewedOn'])->diffForHumans();
                    $portfolio['lastViewedOn'] = $diff;
                    $portfolioArr[] = $portfolio;
                    // $portfolio['']
                }
                $profile['portfolio'] = $portfolioArr;
                $profile['image'] = $this->getImageFromS3($profile['id'], "Profile");
                $profileArr[] = $profile;
            }

            $response['status'] = true;
            $response['message'] = 'Valid results';
            $response['result'] = $profileArr;
        }else{
            $response['status'] = false;
            $response['message'] = "No resource found";
        }

        return $response;
    }

    public function store(Request $request)
    {
        //
        // die('here');

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $loggedInUser = $request->user()->toArray();
        $createdBy = $loggedInUser['id'];
        $updatedBy = $loggedInUser['id'];

        // echo "<pre>";print_r($userRequest);die;

        $validator = Validator::make($userRequestValidate, [
            'email' => 'required|string|email|unique:users',
            // 'name' => 'required|string',
            // 'mobile' => 'unique:users,mobile_number,NULL',
            // 'title'  => 'required',
            // 'portFolio_Url'  => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'error' => $validator->errors()
            ]);      
        }

        $user = new User([
            'name' => $userRequest->resource_name,
            'email' => $userRequest->email,
            // 'password' => bcrypt($userRequest->password),
            'user_role' => 'Profile',
            // 'userType' => 'employee',
            'status' => $userRequest->status,
            'createdBy' => $createdBy,
            'updatedBy' => $updatedBy,
        ]);

        if($user->save()){
            // die('here');
            // try {
    // Mail::to($user->email, '$user->name')->send(new VerificationMail($user));

        //     $validator = Validator::make($userRequestValidate, [
        //     'email' => 'required|string|email|unique:users',
        //     // 'mobile' => 'unique:users,mobile_number,NULL',
        //     // 'title'  => 'required',
        //     'portFolio_ Url'  => 'required',
        // ]);

        // if($validator->fails()){
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Validation Error',
        //         'error' => $validator->errors()
        //     ]);      
        // }


                $Profile = new Profile;

                $Profile->user_id = $user->id;
                $Profile->resource_name = $userRequest->resource_name; 
                $Profile->description = $userRequest->description; 
                $Profile->email = $userRequest->email; 
                $Profile->mobile = $userRequest->mobile; 
                $Profile->Skype_id = $userRequest->Skype_id; 
                $Profile->LinkedIn_id = $userRequest->LinkedIn_id; 
                $Profile->image = $userRequest->image; 
                $Profile->master_notes = $userRequest->master_notes; 
                $Profile->createdBy = $createdBy;
                $Profile->updatedBy = $updatedBy;
                $Profile->status = $userRequest->status;

                if($Profile->save()){

                    $HistoryData['description'] = "Resource created by ".ucwords($this->getAdminName($createdBy));
                    $HistoryData['activityType'] = "new_profile";
                    $HistoryData['loginType'] = "adminLogin";
                    $HistoryData['createdBy'] = $createdBy;
                    $HistoryData['updatedBy'] = $updatedBy;
                    $this->addHistory($HistoryData);

                    $Profile->image = $this->getImageFromS3($Profile->id, 'Profile');
                    $Profile->portfolio = array();
                    $response['status'] = true;
                    $response['message'] = 'Successfully created resource.';
                    $response['user'] = $Profile;
                    // $response['user'] = $user;
                }else{    
                    $response['status'] = false;
                    $response['message'] = 'error occured';
                }
        }else{
            $response['status'] = false;
            $response['message'] = 'Error occurred';
        }

        return $response;
    }

    public function portfolio(Request $request, $profile_id)
    {
        //
        // die('here');

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));
        // echo "<pre>";print_r($userRequest);die;

        
        $loggedInUser = $request->user()->toArray();
        $createdBy = $loggedInUser['id'];
        $updatedBy = $loggedInUser['id'];

        // echo "<pre>";print_r($userRequest);die;

        // $validator = Validator::make($userRequestValidate, [
        //     'email' => 'required|string|email|unique:users',
        //     'name' => 'required|string',
        //     // 'mobile' => 'unique:users,mobile_number,NULL',
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
        // echo $profile_id;die;
        $Profile = Profile::find($profile_id);
        $getProfileName = $this->getResourceName($profile_id);
        // $Profile['image'] = $this->getImageFromS3($ProfileId, "Profile");
        $ResourceName = $getProfileName['resource_name'];

        if(!empty($Profile)){

            
            foreach($userRequest as $portfolio){

                

                $Portfolio = new Portfolio([
                    'profile_id' => $profile_id,
                    'profile_title' => $portfolio->profile_title,
                    'portfolio_url' => $portfolio->portfolio_url,
                    'portfolio_pdf_url' => $portfolio->portfolio_pdf_url,
                    'validity' => $portfolio->validity,
                    'profile_notes' => $portfolio->profile_notes,
                    // 'shares' => $portfolio->shares,
                    // 'views' => $portfolio->views,
                    'status' => $portfolio->status,
                    'createdBy' => $createdBy,
                    'updatedBy' => $updatedBy,
                ]);

                $Portfolio->save();
                $portfolioArr[] = $Portfolio;
            }

                $HistoryData['description'] = "Portfolio added to the profile ".$ResourceName;
                $HistoryData['activityType'] = "new_portfolio";
                $HistoryData['loginType'] = "adminLogin";
                $HistoryData['createdBy'] = $createdBy;
                $HistoryData['updatedBy'] = $updatedBy;
                $this->addHistory($HistoryData);

            $Profile->portfolio = $portfolioArr;
            $response['status'] = true;
            $response['message'] = 'PortFolio added Successfully';
            $response['profile'] = $Profile;


        // if($Portfolio->save()){
        //     $Profile->portfolio = $Portfolio;

        //             $response['status'] = true;
        //             $response['message'] = 'PortFolio added Successfully';
        //             $response['profile'] = $Profile;
        // }else{
        //     $response['status'] = false;
        //     $response['message'] = 'Error occurred';
        // }
    }else{
                    $response['status'] = false;
            $response['message'] = 'We couldn’t find that resource in our records. Try again.';
    }

        

        return $response;
    }

    public function updatePortfolio(Request $request, $portfolio_id){
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $user = $request->user()->toArray();
        $updatedBy = $user['id'];

        // $Profile = Profile::find($userRequest->profile_id);
            $Portfolio = Portfolio::find($portfolio_id);
            if(!empty($Portfolio)){
                $Profile = Profile::find($Portfolio->profile_id);
                $Portfolio->profile_title = $userRequest->profile_title;
                $Portfolio->portfolio_url = $userRequest->portfolio_url;
                $Portfolio->portfolio_pdf_url = $userRequest->portfolio_pdf_url;
                $Portfolio->profile_notes = $userRequest->profile_notes;
                $Portfolio->status = $userRequest->status;
                $Portfolio->updatedBy = $updatedBy;

                if($Portfolio->save()){
                    $Profile->portfolio = $Portfolio;
                    $response['status'] = true;
                    $response['message'] = 'profile updated Successfully';
                    $response['profile'] = $Profile;
                }else{
                    $response['status'] = false;
                    $response['message'] = 'We couldn’t find that resource in our records. Try again.';
                }
            }else{
                $response['status'] = false;
                $response['message'] = 'We couldn’t find that record. Try again.';
            }
        
        


        return $response;
    }

    public function addNotes(Request $request, $portfolio_id){

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $user = $request->user()->toArray();
        $updatedBy = $user['id'];

        $portfolio = Portfolio::find($portfolio_id);
        if(!empty($portfolio)){
            $portfolio->profile_notes = $userRequest->profile_notes;
            $portfolio->updatedBy = $updatedBy;
            // $Admin = Admin::find($userRequest->admin_id);
            // if(!empty($Admin)){

            if($portfolio->save()){
                $response['status'] = true;
                $response['message'] = 'Notes updated Successfully';
            }else{
                $response['status'] = false;
                $response['message'] = 'Error occurred';
            }
        }else{
            $response['status'] = false;
            $response['message'] = 'We couldn’t find that share in our records. Try again.';
        }

        return $response;
    }

    public function checkUrlValidity(Request $request){

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));


        if(!$this->vaidationProfileUrl($userRequest->url)){
            $response['status'] = false;
            $response['message'] = '{'.$userRequest->url.'} Url does not exist';
            return $response;
        }else{
            $response['status'] = true;
            $response['message'] = "{".$userRequest->url."} is a valid url";
        }
        return $response;
    }

    public function skills(){
        $Portfolio = Portfolio::select('profile_title')->distinct()->get()->toArray();
        if(!empty($Portfolio)){
            foreach($Portfolio as $skills){
                $profileTitle[] = $skills['profile_title'];
            }
            $response['status'] = true;
            $response['message'] = "skills";
            $response['skills'] = $profileTitle;
        }else{
            $response['status'] = false;
            $response['message'] = "No skills found";
        }
        
        return $response;
        // echo "<pre>";print_r($profileTitle);
    }

    public function skilledProfiles(Request $request){
        $requestData = trim(file_get_contents("php://input"));
        $requestData = rtrim($requestData, ":");
        $userRequest = (json_decode($requestData, true));

        $selectPortfolio = Portfolio::where("profile_title" , "=", $userRequest['skill'])->get()->toArray();

        if(!empty($selectPortfolio)){
            foreach ($selectPortfolio as $portfolio) {
                $portfolio['portfolio_pdf_url'] = $this->getImageFromS3($portfolio['id'], 'PortFolio');
                $getProfile = Profile::find($portfolio['profile_id'])->toArray();

                if(!empty($getProfile)){
                    $getProfile['image'] = $this->getImageFromS3($portfolio['profile_id'], "Profile");
                    $portfolio['profile'] = $getProfile;
                    $profileArr[] = $portfolio;

                }
            }
            $response['status'] = true;
            $response['result'] = $profileArr;
        }else{
            $response['status'] = false;
            $response['message'] = "Nothing found";
        }
        

        
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
        
        $User = User::find($id);
        if($User){
            $userProfile = Profile::where('id', $id)->first()->toArray();
            if($userProfile){
                $userProfile['image'] = $this->getImageFromS3($id, 'Profile');
                $response['status'] = true;
                    $response['message'] = 'Valid User';
                    // $response['user'] = $User;
                    $response['userProfile'] = $userProfile;
            }else{
                $response['status'] = false;
                $response['message'] = 'Error occurred.';
            }

        }else{
            $response['status'] = false;
            $response['message'] = 'We couldn’t find that resource in our records. Try again.';
        }
        return $response;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //code to view the edit page


    }

    public function upload(Request $request){
        $requestData = trim(file_get_contents("php://input"));
        $requestData = rtrim($requestData, ":");
        $userRequest = (json_decode($requestData, true));
        $getFileData = $userRequest['file'];

        $user = $request->user()->toArray();
        // echo "<pre>";print_r($getImageData);die;


        if (!empty($getFileData)) {

            $response = array();
                $ImageArray = explode(";base64,", $getFileData);           //explode the image
                $forType = $ImageArray[0];                                  //get image type
                $forImage = $ImageArray[1];                                 //base64 encrypted image    
                $toGetType = explode("/", $forType);
                
                $extn_type = $toGetType[1];
                if($extn_type == 'svg+xml'){
                    $extn_type = 'svg';
                }
                
                $file_base64 = base64_decode($forImage);
                if($extn_type == 'pdf'){
                    $storage = 'pdf';
                }else{
                    $storage = 'profile';
                }
                // echo "<pre>";print_r($image_base64);die;                               //decoding the base64 image to a normal image
                $fileSave = 'PSM_'.date('His-mdY')."_".uniqid() .".".$extn_type;          //unique name for the image

                if(Storage::disk('s3')->put($storage.'/' . $fileSave, $file_base64)) {                //uploadind the image to the s3 bucket
                    $response['status'] = true;
                    $response['message'] = 'file uploaded.';
                    // $response['filename'] = 'file uploaded.';
                    $response['file']=  $fileSave;
                   //retrieving the image from the s3 bucket
                }else{
                    $response['status'] = false;
                    $response['message'] = 'Error saving the file.';
                }
        }else{
            $response['status'] = false;
            $response['message'] = 'No file uploaded.';
        }
        return $response;
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

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $loggedInUser = $request->user()->toArray();
        $updatedBy = $loggedInUser['id'];

        // $User = User::find($id);
        // if(empty($User)){
        //     $response['status'] = false;
        //     $response['message'] = 'No user exist';
        //     return $response;
        // }

        // $validator = Validator::make($userRequestValidate, [
        //     'email' => 'required|string|email|unique:users,email,'.$User->id.',id',
        //     // 'password' => 'required',
        //     // 'user_role'  => 'required',
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

                $Profile = Profile::find($id);
                if(!empty($Profile)){

                    $user_id = $Profile->user_id;
                    if(empty($user_id)){
                        $response['status'] = false;
                        $response['message'] = 'Error. Please contact your administrator.';
                        return $response;
                    }

                    $User = User::find($user_id);
                            if(empty($User)){
                        $response['status'] = false;
                        $response['message'] = 'Sorry. We could not find the user.';
                        return $response;
                    }

                            $validator = Validator::make($userRequestValidate, [
                            'email' => 'required|string|email|unique:users,email,'.$User->id.',id',

                        ]);

                    if($validator->fails()){
                        return response()->json([
                            'status' => false,
                            'message' => 'Validation Error',
                            'error' => $validator->errors()
                        ]);      
                    }

                    $User->email = $userRequest->email;
                    $User->name = $userRequest->resource_name;
                    $User->updatedBy = $updatedBy;

                    $User->save();

                    $Profile->resource_name = $userRequest->resource_name; 
                    $Profile->description = $userRequest->description; 
                    $Profile->email = $userRequest->email; 
                    $Profile->mobile = $userRequest->mobile; 
                    $Profile->Skype_id = $userRequest->Skype_id; 
                    $Profile->LinkedIn_id = $userRequest->LinkedIn_id; 
                    $Profile->image = $userRequest->image; 
                    $Profile->master_notes = $userRequest->master_notes; 
                    $Profile->updatedBy = $updatedBy;

                    if($Profile->save()){
                        $Profile->image = $this->getImageFromS3($id, "Profile");
                        $response['status'] = true;
                        $response['message'] = 'Successfully updated resource!';
                        // $response['user'] = $User;
                        $response['userProfile'] = $Profile;
                    }else{    
                        $response['status'] = false;
                        $response['message'] = 'error occured';
                    }
            }else{
                $response['status'] = false;
                $response['message'] = 'No such profile';
            }
        

        

        return $response;

    }

    public function updateProfileStatus(Request $request, $profile_id){

        $loggedInUser = $request->user()->toArray();
        $updatedBy = $loggedInUser['id'];

        $profile = Profile::find($profile_id);

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        if(!empty($profile)){
            if(($userRequest->status == 1)){
                $status  = true;
                // $response['status'] = false;
                // $response['message'] = 'Invalid status value';
                // return $response;
            }elseif($userRequest->status == 0){
                $status  = false;
            }else{
                $status  = $userRequest->status;
            }

            if(!is_bool($status)){
                $response['status'] = false;
                $response['message'] = 'Invalid status value';
                return $response;
            }

            
            $profile->status = $userRequest->status;
            $profile->updatedBy = $updatedBy;
            if($profile->save()){
                $portfolio = Portfolio::where('profile_id',$profile_id)->update(['status' => $userRequest->status]);

                // ->where("posts.user_id", '=',  $user_id)
                // ->update(['posts.status'=> 'closed'])
                $response['status'] = true;
                $response['message'] = 'status updated';
            }else{
                $response['status'] = false;
                $response['message'] = 'Error occurred';
            }
        }else{
            $response['status'] = false;
            $response['message'] = 'Invalid profile';
        }

        return $response;
        // $userProfile['image'] = $this->getImageFromS3($id);
    }

    public function updatePortfolioStatus(Request $request, $portfolio_id){

        $loggedInUser = $request->user()->toArray();
        $updatedBy = $loggedInUser['id'];
        $PortFolio = PortFolio::find($portfolio_id);

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        if(!empty($PortFolio)){
            if(($userRequest->status == 1)){
                $status  = true;
                // $response['status'] = false;
                // $response['message'] = 'Invalid status value';
                // return $response;
            }elseif($userRequest->status == 0){
                $status  = false;
            }else{
                $status  = $userRequest->status;
            }

            if(!is_bool($status)){
                $response['status'] = false;
                $response['message'] = 'Invalid status value';
                return $response;
            }

            
            $PortFolio->status = $userRequest->status;
            $PortFolio->updatedBy = $updatedBy;
            if($PortFolio->save()){                
                // ->where("posts.user_id", '=',  $user_id)
                // ->update(['posts.status'=> 'closed'])
                $response['status'] = true;
                $response['message'] = 'status updated';
            }else{
                $response['status'] = false;
                $response['message'] = 'Error occurred';
            }
        }else{
            $response['status'] = false;
            $response['message'] = 'Invalid profile';
        }

        return $response;
    }

    public function extendValidity(Request $request, $portFolio_Id){
        $user = $request->user()->toArray();
        $updatedBy = $user['id'];

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));
        $extendedValidity = $userRequest->extendedValidity;
        $validityType = $userRequest->validityType;
        return $this->extendTheValidity($validityType, $portFolio_Id, $extendedValidity, $updatedBy);
    }

    public function assistance(){
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        if(!empty($userRequest)){
            $Assistance = new Assistance;
            if(!(is_null($Assistance))){
                // $AdminEmail = 'hello@millipixels.com';
                $AdminEmail = 'vishal.parkash@millipixels.com';
                $Assistance->name = $userRequest->name;
                $Assistance->email = $userRequest->email;
                $Assistance->message = $userRequest->message;

                if($Assistance->save()){
                    $mail = "Hello<br>";
                    $mail .= $userRequest->name." is looking for assistance to login to the admin account. The details are are follows:<br>";
                    $mail .= "Name: ".$userRequest->name."<br>";
                    $mail .= "Email: ".$userRequest->email."<br>";
                    $mail .= "Message: ".$userRequest->message."<br>";

                    Mail::to($AdminEmail)->send(new AssistanceMail($mail));
                    $response['status'] = true;
                    $response['message'] = 'Thank you for contacting us. We will be getting back to you soon.';
                }else{
                    $response['status'] = false;
                    $response['message'] = 'error occured';
                }
            }

        }else{
            $response['status'] = false;
            $response['message'] = 'Please enter valid data';
        }

        return $response;
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
