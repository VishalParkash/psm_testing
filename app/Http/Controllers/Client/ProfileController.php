<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Share;
use App\Client;
use App\Profile;
use App\Portfolio;
use App\Gallery;
use App\SharePortfolio;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\CommonTrait;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
use CommonTrait;
       public function __construct()
{
    $this->middleware('auth');
    $this->middleware(function ($request, $next) {
    $this->id = Auth::user()->id;
    $this->email = Auth::user()->email;
        $loggedInUserId = ($this->id);
        $loggedInUserEmail = ($this->email);

        $User = User::find($loggedInUserId);
        if(empty($User)){
            $status = false;
            $response['message'] = 'Unauthenticated.';
            return response()->json($response);

            // if($User->user_role != 'client' ){
            //     $status = false;
            //     $response['message'] = 'Unauthenticated.';
            //     return response()->json($response);
            // }
        }
        return $next($request);
    });
}
    public function index($id=false)
    {
        //$id is the client ID
        $id = 'vishal.parkash@millipixels.com';
        $getProfiles = CreateShare::where('clientGoogle_email', '=', $id)->get()->toArray();
        // echo "<pre>";print_r($getProfiles);die;
        if(!empty($getProfiles)){
            $newArray = array();
            $Profiles = array();
        foreach($getProfiles as $profile){
            $Ids = $profile['profileShared'];
            $Profiles[$profile['projectName']] = $Ids;
            $ProfileDetails[$profile['projectName']] = $profile;

            // array_push($Profiles,$Ids);

        }
        // echo "<pre>";print_r($Profiles);die;

        // $profileStr = implode(',', $Profiles);
        // $ProfileArr = explode(',', $profileStr);
        foreach ($Profiles as $project => $id) {

            $ids = explode(',', $id);
            // echo "<pre>";print_r($ids);
            foreach ($ids as $user_id) {
                # code...
                if(Profile::where('user_id', '=', $user_id)->first()) {
                    $details[] = Profile::where('user_id', '=', $user_id)->first()->toArray();
                }


            }
            $ProfileDetails[$project]['Profile'] = $details;
            $response['status'] = true;
            $response['details'] = $ProfileDetails;
            $response['message'] = 'Valid records';
        }
        }else{
            $response['status'] = false;
            $response['message'] = 'Invalid user';
        }
        


        // echo "<pre>";print_r($ProfileDetails);die;
        return $response; 
        
    }


    public function countPortfolioViews(Request $request, $portfolio_id,$share_id){

        if(!empty($share_id)){
            $share = Share::find($share_id);
            if(empty($share)){
                $response['status'] = false;
                $response['message'] = "Invalid share id";
                return $response;
            }
        }
        if(!empty($portfolio_id)){
            $loggedInUser = $request->user()->toArray();
            $ClientId = $loggedInUser['id'];
            
            $clientName = $this->getClientName($ClientId);

            $Portfolio = Portfolio::find($portfolio_id);
            $SharePortfolio = SharePortfolio::where('portfolio_id', $portfolio_id)
                                            ->where('share_id', $share_id)
                                            ->first();

            if(!empty($Portfolio)){
                    $getResourceName = $this->getResourceName($Portfolio->profile_id)['resource_name'];

                    $Portfolio->views++;
                    $Portfolio->lastViewedOn = Carbon::now();
                    $SharePortfolio->lastViewedOn = Carbon::now();
                    $SharePortfolio->save();
                    // if($Portfolio->increment('views')) {
                    if($Portfolio->save()) {
                        
                        $HistoryData['share_id'] = $share_id;
                        $HistoryData['client_id'] = $ClientId;
                        $HistoryData['description'] = $clientName." viewed profile of ".$getResourceName;
                        $HistoryData['activityType'] = "profiles_viewed";
                        $HistoryData['loginType'] = "clientLogin";
                        $HistoryData['createdBy'] = $ClientId;
                        $HistoryData['updatedBy'] = $ClientId;
                        $this->addHistory($HistoryData);
                        $response['status'] = true;
                    }else{
                        $response['status'] = false;
                    }
                             
            }else{
                $response['status'] = false;
                $response['message'] = "Invalid profile.";
            }
        }else{
            $response['status'] = false;
        }
        return $response;
    }

    public function profiles($queryString){

        $getShare = Share::where('queryString','=', $queryString)
                            ->where('status', 1)
                            ->first();
        if(!empty($getShare)){
            $getClient = Client::where('share_id', '=', $getShare->id)
                            ->where('clientEmail', '=', $this->email)
                            ->first();

        if(!empty($getClient)){
                    
                    $getClient = $getClient->toArray();
                    $getShare = Share::find($getClient['share_id'])->toArray();
                    $getClient['share_details'] = $getShare;
                    $getClient['share_title'] = $this->getShareTitle($getClient['share_id']);
                    $getPortfolios = $getShare['profileShared'];
                    $Portfolio_ids = explode(',', $getPortfolios);
                    if(!empty($Portfolio_ids)){
                        foreach($Portfolio_ids as $Portfolio_id){
                            $getPortfolio = Portfolio::find($Portfolio_id);
                            if(!empty($getPortfolio)){
                                $getPortfolio['resource_name']= $this->getResourceName($getPortfolio->profile_id)['resource_name'];
                                $getPortfolio['profile_url']= $getPortfolio->portfolio_url;
                                $getPortfolio['profile_title']= $getPortfolio->profile_title;
                                $getPortfolio['id']= $getPortfolio->id;
                                $getPortfolio['pdfUrl']= $this->getImageFromS3($getPortfolio->id, 'Portfolio');

                                $getPortfolio['projectName'] = json_decode($getPortfolio->projectName);
                                $getPortfolio['technologiesUsed'] = json_decode($getPortfolio->technologiesUsed);
                                $getPortfolio['image'] = $this->getImageFromS3($getPortfolio->profile_id, 'Profile');
                                $getPortfolio['bannerImage'] = $this->getImageFromS3($getPortfolio->profile_id, 'profileBannerImage');
                                
                                $getResource = Profile::find($getPortfolio->profile_id, ['education']);
                                if(!empty($getResource)){
                                    $getPortfolio['education'] = json_decode($getResource->education);
                                }

                                $gallery = array();
                                $Gallery = Gallery::where('profile_id', $getPortfolio->profile_id)->get();
                                if(!empty($Gallery)){
                                    $Gallery = $Gallery->toArray();
                                    
                                    foreach($Gallery as $galleryImages){
                                        $Image['ImageId'] = md5(uniqid());
                                        $Image['file'] = $galleryImages['galleryImage'];
                                        $Image['fileUrl'] = $this->getImageFromS3($galleryImages['id'], "Gallery");
                                        $gallery[] = $Image;
                                    }
                                }
                                if(!empty($gallery)){
                                    $getPortfolio['gallery'] = $gallery;
                                }else{
                                    $getPortfolio['gallery'] = array();
                                }
                            }
                            if(!empty($getPortfolio)){
                                $ProfileArr[] = $getPortfolio;
                            }
                            
                        }
                    }
                    if(!empty($ProfileArr)){
                        $getClient['profiles'] = $ProfileArr;
                    }else{
                        $getClient['profiles'] = array();
                    }
                    
                    $response['status'] = true;
                    $response['result'] = $getClient;
                    $response['message'] = "valid result";
                }else{
                    $response['status'] = false;
                    $response['message'] = "We couldn't find the record.";
                }
            }else{
                $response['status'] = 401;
                $response['message'] = 'Unauthenticated';
            }
        
                return $response;
    }

    public function getChange($UpdatedAt,$queryString){
        $Portfolio = array();
        $UpdatedAt = Carbon::parse($UpdatedAt);
        $getShare = Share::where('queryString','=', $queryString)
                            ->where('status', 1)
                            ->first();
        if(!empty($getShare)){

            if($getShare->updated_at == $UpdatedAt){
                    $response['status'] = false;
                    $response['message'] = "No change";
                }elseif($UpdatedAt > $getShare->updated_at){
                    $response['status'] = false;
                    $response['message'] = "Invalid date";;
                }else{
                    $getClient = Client::where('share_id', '=', $getShare->id)
                            ->where('clientEmail', '=', $this->email)
                            ->first();

                    if(!empty($getClient)){
                           
                        $getClient = $getClient->toArray();
                        $getShare = Share::find($getClient['share_id'])->toArray();
                        $getClient['share_details'] = $getShare;
                        $getClient['share_title'] = $this->getShareTitle($getClient['share_id']);
                        $getPortfolios = $getShare['profileShared'];
                        $Portfolio_ids = explode(',', $getPortfolios);
                        if(!empty($Portfolio_ids)){
                            foreach($Portfolio_ids as $Portfolio_id){
                                $getPortfolio = Portfolio::find($Portfolio_id);
                                if(!empty($getPortfolio)){
                                    $getPortfolio = $getPortfolio->toArray();
                                    $getPortfolio['resource_name']= $this->getResourceName($getPortfolio['profile_id'])['resource_name'];
                                    $getPortfolio['profile_url']= $getPortfolio['portfolio_url'];
                                    $getPortfolio['profile_title']= $getPortfolio['profile_title'];
                                    $getPortfolio['id']= $getPortfolio['id'];
                                    $portfolio['projectName'] = json_decode($getPortfolio['projectName']);
                                    $portfolio['technologiesUsed'] = json_decode($getPortfolio['technologiesUsed']);
                                    $getPortfolio['pdfUrl']= $this->getImageFromS3($getPortfolio['id'], 'Portfolio');
                                    $getPortfolio['image'] = $this->getImageFromS3($getPortfolio['profile_id'], 'Profile');
                                    $getPortfolio['bannerImage'] = $this->getImageFromS3($getPortfolio['profile_id'], 'profileBannerImage');
                                    
                                    $getResource = Profile::find($getPortfolio['profile_id'], ['education']);
                                    if(!empty($getResource)){
                                        $getPortfolio['education'] = json_decode($getResource->education);
                                    }

                                    $gallery = array();
                                    $Gallery = Gallery::where('profile_id', $getPortfolio['profile_id'])->get();
                                    if(!empty($Gallery)){
                                        $Gallery = $Gallery->toArray();
                                        
                                        foreach($Gallery as $galleryImages){
                                            $Image['ImageId'] = md5(uniqid());
                                            $Image['file'] = $galleryImages['galleryImage'];
                                            $Image['fileUrl'] = $this->getImageFromS3($galleryImages['id'], "Gallery");
                                            $gallery[] = $Image;
                                        }
                                    }
                                    if(!empty($gallery)){
                                        $getPortfolio['gallery'] = $gallery;
                                    }else{
                                        $getPortfolio['gallery'] = array();
                                    }
                                }
                                $ProfileArr[] = $getPortfolio;
                            }
                        }
                        $getClient['profiles'] = $ProfileArr;
                        $response['status'] = true;
                        $response['result'] = $getClient;
                        $response['message'] = "valid result";
                    }
                }


            
        }else{
            $response['status'] = 401;
            $response['message'] = "Unauthenticated";
        }
        return $response;                          
        // if(!empty($share_id)){
        //     $Share = Share::find($share_id);
        //     // if(!empty($Share)){
        //     //     if($Share->updated_at == $UpdatedAt){
        //     //         return false;
        //     //     }else{

        //     //     }
        //     // }
        // }else{
        //     $response['status'] = false;
        //     $response['message'] = "Invalid Share";
        // }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
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
