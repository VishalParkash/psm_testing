<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Profile;
use App\Portfolio;
use App\SharePortfolio;
use App\Assistance;
use App\Gallery;
use App\Share;
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


    public function list(){
        $Profiles = Profile::all()->toArray();
        if($Profiles){
            $profileArr = array();
            foreach($Profiles as $profile){
                $portfolioList = Portfolio::where('profile_id', $profile['id'])->get();
                if(!empty($portfolioList)){
                    $portfolioList = $portfolioList->toArray();
                    if(!empty($portfolioList)){
                        $portfolioArr = array();
                        $Shares = array();
                        foreach($portfolioList as $portfolio){

                            $portfolio['projectName'] = json_decode($portfolio['projectName']);
                            $portfolio['technologiesUsed'] = json_decode($portfolio['technologiesUsed']);
                            if(!empty($portfolio['lastViewedOn'])){
                                $diff = Carbon::parse($portfolio['lastViewedOn'])->diffForHumans();
                                $portfolio['lastViewedOn'] = $diff;
                            }
                            $portfolioArr[] = $portfolio;
                        }
                    }else{
                        $portfolioArr = array();
                    }
                }

                $gallery = array();
                $Gallery = Gallery::where('profile_id', $profile['id'])->get();
                if(!empty($Gallery)){
                    $Gallery = $Gallery->toArray();
                    // foreach($Gallery as $galleryImages){
                    //     $gallery[] = $this->getImageFromS3($galleryImages['id'], "Gallery");
                    // }

                    //
                            // $count = 1;
                            
                        foreach($Gallery as $galleryImages){
                            // $count = time();
                            $Image['ImageId'] = md5(uniqid());
                            $Image['file'] = $galleryImages['galleryImage'];
                            $Image['fileUrl'] = $this->getImageFromS3($galleryImages['id'], "Gallery");
                            $gallery[] = $Image;
                            // $count++;
                        }
                    //

                }
                
                $profile['image'] = $this->getImageFromS3($profile['id'], 'Profile');
                $profile['bannerImage'] = $this->getImageFromS3($profile['id'], 'profileBannerImage');
                $profile['galleryImages'] = $gallery;
                $profile['education'] = json_decode($profile['education']);
                $profile['portfolio'] = $portfolioArr;
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

    public function createProfile(Request $request)
    {
        $this->id = Auth::user()->id;
        $createdBy = $this->id;
        $updatedBy = $this->id;
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $resourceArr = $userRequest->resource;
        $ProfilesArr = $userRequest->profiles;
        $validator = Validator::make($userRequestValidate['resource'], [
            'email' => 'required|string|email|unique:users',
            'resource_name' => 'required|string',
            'image' => 'required|string',
            'bannerImage' => 'required|string',
            'education' => 'required',
            'galleryImages' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'error' => $validator->errors()
            ]);      
        }

        $user = new User([
            'name' => $resourceArr->resource_name,
            'email' => $resourceArr->email,
            'user_role' => 'Profile',
            'status' => 1,
            'createdBy' => $createdBy,
            'updatedBy' => $updatedBy,
        ]);

        if($user->save()){
  
            $Profile = new Profile();
            if(!is_null($Profile)){
                $Profile->user_id = $user->id;
                $Profile->resource_name = $resourceArr->resource_name; 
                $Profile->email = $resourceArr->email;  
                $Profile->image = $resourceArr->image; 
                $Profile->bannerImage = $resourceArr->bannerImage; 
                $Profile->education = json_encode($resourceArr->education); 
                $Profile->createdBy = $createdBy;
                $Profile->updatedBy = $updatedBy;
                $Profile->status = 1;
            }
            
            try{
                if($Profile->save()){
                    if(!empty($resourceArr->galleryImages)){
                        $gallery = array();
                        foreach($resourceArr->galleryImages as $galleryImages){
                            $Gallery = new Gallery();
                            $Gallery->profile_id = $Profile->id;
                            $Gallery->galleryImage = $galleryImages;
                            $Gallery->createdBy = $this->id;
                            $Gallery->updatedBy = $this->id;
                            try{
                                if($Gallery->save()){
                                    $gallery[] = $this->getImageFromS3($Gallery->id, "Gallery");
                                }
                            }catch(\Exception $ex){
                                $response['status'] = false;
                                // $response['message'] = $ex->getMessage();
                                $response['message'] = "User has been saved. Something went wrong while saving the gallery images. Please contact your administrator";
                                return $response;
                            }   
                        }    
                    }

                    $Portfolio = new Portfolio([
                        'profile_id' => $Profile->id,
                        'profile_title' => $ProfilesArr->profile_title,
                        'metaProfileTitle' => $ProfilesArr->metaProfileTitle,
                        'description' => $ProfilesArr->description,
                        'totalExperience' => $ProfilesArr->totalExperience,
                        'projectName' => json_encode($ProfilesArr->projectName),
                        'technologiesUsed' => json_encode($ProfilesArr->technologiesUsed),
                        'focusArea' => $ProfilesArr->focusArea,
                        'careerHighlights' => $ProfilesArr->careerHighlights,
                        'professionalSummary' => $ProfilesArr->professionalSummary,
                        'devStack' => $ProfilesArr->devStack,
                        'availability' => $ProfilesArr->availability,
                        'portfolio_url' => $ProfilesArr->portfolio_url,
                        'portfolio_pdf_url' => $ProfilesArr->portfolio_pdf_url,
                        'profile_notes' => $ProfilesArr->profile_notes,
                        'status' => $ProfilesArr->status,
                        'createdBy' => $createdBy,
                        'updatedBy' => $updatedBy,
                    ]);

                    $Portfolio->save();
                    $Portfolio->projectName = json_decode($Portfolio->projectName);
                    $Portfolio->technologiesUsed = json_decode($Portfolio->technologiesUsed);
                
                    $HistoryData['description'] = "Profile added to the resource ".$resourceArr->resource_name;
                    $HistoryData['activityType'] = "new_portfolio";
                    $HistoryData['loginType'] = "adminLogin";
                    $HistoryData['createdBy'] = $createdBy;
                    $HistoryData['updatedBy'] = $updatedBy;
                    $this->addHistory($HistoryData);

                    $HistoryData['description'] = "Resource created by ".ucwords($this->getAdminName($createdBy));
                    $HistoryData['activityType'] = "new_profile";
                    $HistoryData['loginType'] = "adminLogin";
                    $HistoryData['createdBy'] = $createdBy;
                    $HistoryData['updatedBy'] = $updatedBy;
                    $this->addHistory($HistoryData);

                    $Profile->image = $this->getImageFromS3($Profile->id, 'Profile');
                    $Profile->bannerImage = $this->getImageFromS3($Profile->id, 'profileBannerImage');
                    $Profile->galleryImages = $gallery;
                    $Profile->education = json_decode($Profile->education);
                    $Profile->portfolio = array($Portfolio);
                    $response['status'] = true;
                    $response['message'] = 'Successfully created resource.';
                    $response['result'] = $Profile;
                }
            }catch(\Exception $ex){
                $response['status'] = false;
                $response['message'] = "Profile cannot be saved. Please contact your administrator.";
                // $response['message'] = $ex->getMessage();
                return $response;
            }
        }else{
            $response['status'] = false;
            $response['message'] = 'Error occurred';
        }

        return $response;
    }

    public function store(Request $request)
    { //Previous function to create resource
        $this->id = Auth::user()->id;
        $createdBy = $this->id;
        $updatedBy = $this->id;
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        if(empty($userRequestValidate['resource_name'])){
            $response['status'] = false;
            $response['message'] = "Resource name required.";
            return $response;
        }
        $userRequestValidate['name'] = $userRequestValidate['resource_name'];
        if(!empty($userRequestValidate['email'])){
            $validator = Validator::make($userRequestValidate, [
                'email' => 'string|email|unique:users',
                'name' => 'required|string|unique:users',
            ]);
        }else{
            $validator = Validator::make($userRequestValidate, [
                'name' => 'required|string|unique:users',
            ]);
        }

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'error' => $validator->errors()
            ]);      
        }

        $user = new User([
            'name' => $userRequest->resource_name,
            'email' => (!empty($userRequest->email)) ? ($userRequest->email) : (''),
            'user_role' => 'Profile',
            'status' => 1,
            'createdBy' => $createdBy,
            'updatedBy' => $updatedBy,
        ]);

        ($user->save());

            $ProfileValidator = Validator::make($userRequestValidate, [
            'email' => 'string|email|unique:profiles',
            'resource_name' => 'required|string|unique:profiles',
            // 'image' => 'required|string',
            // 'bannerImage' => 'required|string',
            // 'education' => 'required',
            // 'galleryImages' => 'required',
        ]);

        if($ProfileValidator->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'error' => $ProfileValidator->errors()
            ]);      
        }
            $Profile = new Profile();
            if(!is_null($Profile)){

                if(
                (!empty($userRequest->resource_name)) && 
                (!empty($userRequest->email)) &&
                (!empty($userRequest->image)) &&
                (!empty($userRequest->bannerImage)) &&
                (!empty($userRequest->education))){
                    $resoureStatus = 'Publish';
            }else{
                $resoureStatus = 'Draft';
            }

                $Profile->user_id = $user->id;
                $Profile->resource_name = $userRequest->resource_name; 
                $Profile->email = (!empty($userRequest->email)) ? ($userRequest->email) : ('');
                $Profile->image = (!empty($userRequest->image)) ? ($userRequest->image) : ('');
                $Profile->bannerImage = (!empty($userRequest->bannerImage)) ? ($userRequest->bannerImage) : ('');
                $Profile->education = (!empty($userRequest->education)) ? json_encode($userRequest->education) : ('');
                $Profile->resoureStatus = $resoureStatus;
                $Profile->createdBy = $createdBy;
                $Profile->updatedBy = $updatedBy;
                $Profile->status = 1;
            }
            
            try{
                if($Profile->save()){
                // echo "<pre>";print_r($userRequest->galleryImages);die;
                if(!empty($userRequest->galleryImages)){
                    $gallery = array();
                    foreach($userRequest->galleryImages as $galleryImages){
                        $Gallery = new Gallery();
                        $Gallery->profile_id = $Profile->id;
                        $Gallery->galleryImage = $galleryImages;
                        $Gallery->createdBy = $this->id;
                        $Gallery->updatedBy = $this->id;
                        try{
                            if($Gallery->save()){
	                            $Image['ImageId'] = md5(uniqid());
	                            $Image['file'] = $galleryImages;
	                            $Image['fileUrl'] = $this->getImageFromS3($Gallery->id, "Gallery");
	                            $gallery[] = $Image;
                            }
                        }catch(\Exception $ex){
                            $response['status'] = false;
                            $response['message'] = $ex->getMessage();
                            // $response['message'] = "User has been saved. Something went wrong while saving the gallery images. Please contact your administrator";
                            return $response;
                        }
                        
                    }
                    
                }

                $HistoryData['description'] = "Resource created by ".ucwords($this->getAdminName($createdBy));
                $HistoryData['activityType'] = "new_profile";
                $HistoryData['loginType'] = "adminLogin";
                $HistoryData['createdBy'] = $createdBy;
                $HistoryData['updatedBy'] = $updatedBy;
                $this->addHistory($HistoryData);

                $Profile->image = $this->getImageFromS3($Profile->id, 'Profile');
                $Profile->bannerImage = $this->getImageFromS3($Profile->id, 'profileBannerImage');
                $Profile->galleryImages = (!empty($gallery)) ? ($gallery) : ('');
                $Profile->education = json_decode($Profile->education);
                $Profile->portfolio = array();
                $response['status'] = true;
                $response['message'] = 'Successfully created resource.';
                $response['result'] = $Profile;
                // $response['user'] = $user;
                }   
            }catch(\Exception $ex){
                $response['status'] = false;
                $response['message'] = "Profile cannot be saved. Please contact your administrator.";
                // $response['message'] = $ex->getMessage();
                return $response;
            }
        

        return $response;
    }


    public function update(Request $request, $id)
    {
        //

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $loggedInUser = $request->user()->toArray();
        $updatedBy = $loggedInUser['id'];


        $Profile = Profile::find($id);
                if(!empty($Profile)){

                    $user_id = $Profile->user_id;
                    if(empty($user_id)){
                        $response['status'] = false;
                        $response['message'] = 'Error. Please contact your administrator.';
                        return $response;
                    }

                }
        $validator = Validator::make($userRequestValidate, [
            'email' => 'email|unique:users,email,'.$user_id.',id',
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
            'user_role' => 'Profile',
            'status' => 1,
            'createdBy' => $updatedBy,
            'updatedBy' => $updatedBy,
        ]);


                if(!empty($Profile)){

                    $validator = Validator::make($userRequestValidate, [
                        // 'email' => 'required|string|email|unique:profiles,email,'.$id.',id',
                        'resource_name' => 'required|string',
                        // 'image' => 'required|string',
                        // 'bannerImage' => 'required|string',
                        // 'education' => 'required',
                        // 'galleryImages' => 'required',
                    ]);

                    if($validator->fails()){
                        return response()->json([
                            'status' => false,
                            'message' => 'Validation Error',
                            'error' => $validator->errors()
                        ]);      
                    }

                    if(
                        (!empty($userRequest->resource_name)) && 
                        (!empty($userRequest->email)) &&
                        (!empty($userRequest->image)) &&
                        (!empty($userRequest->bannerImage)) &&
                        (!empty($userRequest->education))){
                            $resoureStatus = 'Publish';
                    }else{
                        $resoureStatus = 'Draft';
                    }

                    // $Profile->user_id = $user->id;
                    $Profile->resource_name = $userRequest->resource_name; 
                    $Profile->email = (!empty($userRequest->email)) ? ($userRequest->email) : ('');
                    $Profile->image = (!empty($userRequest->image)) ? ($userRequest->image) : ('');
                    $Profile->bannerImage = (!empty($userRequest->bannerImage)) ? ($userRequest->bannerImage) : ('');
                    $Profile->education = (!empty($userRequest->education)) ? json_encode($userRequest->education) : ('');
                    $Profile->resoureStatus = $resoureStatus;
                    $Profile->updatedBy = $updatedBy;

                    // $Profile->resource_name = $userRequest->resource_name; 
                    // $Profile->email = $userRequest->email;  
                    // $Profile->image = $userRequest->image; 
                    // $Profile->bannerImage = $userRequest->bannerImage; 
                    // $Profile->education = json_encode($userRequest->education); 
                    // $Profile->updatedBy = $updatedBy;
                    // $Profile->status = 1;

                    if($Profile->save()){

                        if(!empty($userRequest->galleryImages)){
                            $gallery = array();
                            // $count = 1;
                            // $count = time();
                            $RemoveGallery = Gallery::where('profile_id', $id)->delete();
                            foreach($userRequest->galleryImages as $galleryImages){
                                $Gallery = new Gallery();
                                $Gallery->profile_id = $id;
                                $Gallery->galleryImage = $galleryImages;
                                $Gallery->createdBy = $updatedBy;
                                $Gallery->updatedBy = $updatedBy;
                                try{
                                    if($Gallery->save()){
                                        $Image['ImageId'] = md5(uniqid());
                                        $Image['file'] = $galleryImages;
                                        $Image['fileUrl'] = $this->getImageFromS3($Gallery->id, "Gallery");
                                        $gallery[] = $Image;
                                        // $count++;
                                        // $gallery[] = $this->getImageFromS3($Gallery->id, "Gallery");
                                    }
                                }catch(\Exception $ex){
                                    $response['status'] = false;
                                    $response['message'] = $ex->getMessage();
                                    // $response['message'] = "User has been saved. Something went wrong while saving the gallery images. Please contact your administrator";
                                    return $response;
                                }
                                
                            }
                            
                        }

                        $Portfolio = Portfolio::where('profile_id', $id)->get();
                        if(!empty($Portfolio)){
                            $Portfolio = $Portfolio->toArray();
                            $portfolioArr = array();
                            foreach($Portfolio as $portfolio){
                                $portfolio['projectName'] = json_decode($portfolio['projectName']);
                                $portfolio['technologiesUsed'] = json_decode($portfolio['technologiesUsed']);
                                $portfolioArr[] = $portfolio;
                            }
                        }
                        $Profile->image = $this->getImageFromS3($id, 'Profile');
                        $Profile->bannerImage = $this->getImageFromS3($id, 'profileBannerImage');
                        $Profile->galleryImages = (!empty($gallery)) ? ($gallery) : ('');
                        $Profile->education = json_decode($Profile->education);
                        $Profile->portfolio = $portfolioArr;
                        $response['status'] = true;
                        $response['message'] = 'Successfully updated resource.';
                        $response['result'] = $Profile;
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

    public function show($id)
    {
        //
        
        $User = User::find($id);
        if($User){
            // $userProfile = Profile::where('id', $id)->first()->toArray();
            $userProfile = Profile::find($id)->first();
            if($userProfile){
                $userProfile->image = $this->getImageFromS3($id, 'Profile');
                $userProfile->bannerImage = $this->getImageFromS3($id, 'profileBannerImage');
                $userProfile->education = json_decode($userProfile->education);
                $response['status'] = true;
                    $response['message'] = 'Valid User';
                    $response['result'] = $userProfile;
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

    public function portfolio(Request $request, $profile_id)
    {

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));
        
        $loggedInUser = $request->user()->toArray();
        $createdBy = $loggedInUser['id'];
        $updatedBy = $loggedInUser['id'];

        $Profile = Profile::find($profile_id);
        $getProfileName = $this->getResourceName($profile_id);

        $ResourceName = $getProfileName['resource_name'];

        if(!empty($Profile)){

            foreach($userRequest as $portfolio){

                $Portfolio = new Portfolio([
                    'profile_id' => $profile_id,
                    'profile_title' => $portfolio->profile_title,
                    'metaProfileTitle' => $portfolio->metaProfileTitle,
                    'description' => $portfolio->description,
                    'totalExperience' => $portfolio->totalExperience,
                    'projectName' => json_encode($portfolio->projectName),
                    'technologiesUsed' => json_encode($portfolio->technologiesUsed),
                    'focusArea' => $portfolio->focusArea,
                    'careerHighlights' => $portfolio->careerHighlights,
                    'professionalSummary' => $portfolio->professionalSummary,
                    'devStack' => $portfolio->devStack,
                    'availability' => $portfolio->availability,
                    'portfolio_url' => $portfolio->portfolio_url,
                    'portfolio_pdf_url' => $portfolio->portfolio_pdf_url,
                    'profile_notes' => $portfolio->profile_notes,
                    'status' => $portfolio->status,
                    'createdBy' => $createdBy,
                    'updatedBy' => $updatedBy,
                ]);

                $Portfolio->save();
                $Portfolio->projectName = json_decode($Portfolio->projectName);
                $Portfolio->technologiesUsed = json_decode($Portfolio->technologiesUsed);
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
                $Portfolio->metaProfileTitle = $userRequest->metaProfileTitle;
                $Portfolio->description = $userRequest->description;
                $Portfolio->totalExperience = $userRequest->totalExperience;
                $Portfolio->projectName = json_encode($userRequest->projectName);
                $Portfolio->technologiesUsed = json_encode($userRequest->technologiesUsed);
                $Portfolio->focusArea = $userRequest->focusArea;
                $Portfolio->careerHighlights = $userRequest->careerHighlights;
                $Portfolio->professionalSummary = $userRequest->professionalSummary;
                $Portfolio->devStack = $userRequest->devStack;
                $Portfolio->availability = $userRequest->availability;
                $Portfolio->portfolio_url = $userRequest->portfolio_url;
                $Portfolio->portfolio_pdf_url = $userRequest->portfolio_pdf_url;
                $Portfolio->profile_notes = $userRequest->profile_notes;
                $Portfolio->status = $userRequest->status;
                $Portfolio->updatedBy = $updatedBy;


                try{
                   ($Portfolio->save());
                        $Portfolio->projectName = json_decode($Portfolio->projectName);
                        $Portfolio->technologiesUsed = json_decode($Portfolio->technologiesUsed);
                        $Profile->portfolio = $Portfolio;
                        $response['status'] = true;
                        $response['message'] = 'profile updated Successfully';
                        $response['profile'] = $Profile;
                    
                }catch(\Exception $ex){
                    $response['status'] = false;
                    $response['message'] = 'We couldn’t find that resource in our records. Try again.';
                    return $response;
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
    

    public function uploadGallery(){
        $this->id = Auth::user()->id;
        $requestData = trim(file_get_contents("php://input"));
        $requestData = rtrim($requestData, ":");
        $userRequest = (json_decode($requestData, true));
        // echo "<pre>";print_r($userRequest);die;
        $images = array();
        // $count = time();
        // $t=
        foreach($userRequest as $fileData){
            // $getImage = $this->uploadFile($getImageData, 'project', $this->id);
            $getImage = $this->uploadFile($fileData['file'], 'gallery');
            $Image['ImageId'] = md5(uniqid());
            $Image['file'] = $getImage;
            $Image['fileUrl'] = $this->getImageUrlFromS3($getImage, "Gallery");
            $images[] = $Image;
            // $count++;
            
        }
        $response['status'] = true;
        $response['result'] = $images;
        return $response;
    }

    // public function addGallery(){
    //     echo $this->id = Auth::user()->id;
    //     $requestData = trim(file_get_contents("php://input"));
    //     $requestData = rtrim($requestData, ":");
    //     $userRequest = (json_decode($requestData, true));


    //     foreach($userRequest as $technologies){
    //         $Technology = new Technology();
    //     if(!is_null($Technology)){
    //         $Technology->technology = $technologies->technology;
    //         $Technology->icon = $technologies->icon;
    //         if($Technology->save()){
    //             $response['status'] = true;
    //             $response['message'] = "icon added";
    //         }
    //     }
    //     }


    //     $response = $this->uploadFile($getImageData, 'gallery');
    //     return $response;
    // }

    public function getGallery($Profile){
        $Gallery = Gallery::where('user_id', $Profile)->get();
        if(!empty($Gallery)){
            $Gallery = $Gallery->toArray();
            if(!empty($Gallery)){
                foreach($Gallery as $gallery){
                    $gallery['galleryImage'] = $this->getImageFromS3($gallery['id'], "Gallery");
                    $galleryImages[] = $gallery;
                }
                if(!empty($galleryImages)){
                    $response['status'] = true;
                    $response['result'] = $galleryImages;
                }
            }else{
                $response['status'] =false;
                $response['message'] ="No images for this profile available.";
            }
        }
        return $response;

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

}
