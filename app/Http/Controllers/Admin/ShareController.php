<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Profile;
use App\Portfolio;
use App\Share;
use App\Client;
use App\History;
use App\SharePortfolio;
use Illuminate\Support\Str;
use App\Http\Traits\CommonTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ShareController extends Controller
{
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

    public function index()
    {
        //
        $profilesData = Profile::all();
        $QueryStr = Str::random(15);
    }

    public function list(){
        $CreateShare = Share::all()->toArray();
        $Records = array();
        $result = array();
        foreach ($CreateShare as $shareRecords) {

            $ClientShare = Client::where('share_id','=', $shareRecords['id'])->get()->toArray();          //client share
            $shareRecords['shareDetails'] = $ClientShare;
            
            $getProfileShared = $shareRecords['profileShared'];
            $ProfileIds = explode(",", $getProfileShared);
            foreach($ProfileIds as $ProfileId){
                $Profile = Profile::where('user_id','=', $ProfileId)->get()->toArray();
                $Profile['image'] = $this->getImageFromS3($ProfileId, "Profile");
                $shareRecords['Profiles'] = $Profile;        
            }
            $result[] = $shareRecords;
        }
        return $result;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $clientAdded =false;
        $QueryStr = Str::random(15);
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $loggedInUser = $request->user()->toArray();
        $createdBy = $loggedInUser['id'];
        $updatedBy = $loggedInUser['id'];

        // $UrlToShare = url('api/client/login/')."/".$QueryStr;
        // $UrlToShare = "https://localhost:3000/".$QueryStr;
        // $UrlToShare = "http://localhost:3001/?key=".$QueryStr;

        $UrlToShare = "https://staging.dg13bjcoiqrz8.amplifyapp.com?key=".$QueryStr;

        $profileShared = $this->validateProfiles($userRequest->profileShared);
        if(!$profileShared){
            $response['status'] = false;
            $response['message'] = "Invalid Profiles shared. Please try again";
            return $response;
        }
        $CreateShare = new Share([
            'share_title' => $userRequest->share_title,
            'project_title' => $userRequest->project_title,
            'clientName' => $userRequest->clientName,
            'queryString' => $QueryStr,
            'share_url' => $UrlToShare,
            'profileShared' => $userRequest->profileShared,
            'EmailSent' => $userRequest->EmailSent,
            'validity' => $userRequest->validity,
            'notes' => $userRequest->notes,
            'status' => $userRequest->status,
            'createdBy' => $createdBy,
            'updatedBy' => $updatedBy,
        ]);

        if($CreateShare->save()){

            $HistoryData['share_id'] = $CreateShare->id;
            $HistoryData['description'] = "Share created by ".ucwords($this->getAdminName($createdBy));
            $HistoryData['activityType'] = "new_share";
            $HistoryData['loginType'] = "adminLogin";
            $HistoryData['createdBy'] = $createdBy;
            $HistoryData['updatedBy'] = $updatedBy;
            $this->addHistory($HistoryData);


            $Ids = $CreateShare->profileShared;
            $ids = explode(',', $Ids);
            foreach($ids as $portfolio_id){
                $this->IncrementCount($portfolio_id, 'shares');
                $getPortFolio = $this->getPortFolio($portfolio_id, $CreateShare->id);
                if(!empty($getPortFolio)){
                    // $getPortFolio['portfolio_id'] = $getPortFolio['id'];
                    
                    $getResourceName = $this->getResourceName($getPortFolio['profile_id']);
                    $getPortFolio['image'] = $this->getImageFromS3($getPortFolio['profile_id'], "Profile");
                    $getPortFolio['resource_name'] = $getResourceName['resource_name'];

                    $getPortFolio['portfolio_id'] = $getPortFolio['id'];
                    $getPortFolio['id'] = $getPortFolio['profile_id'];
                    $getPortFolio['lastViewedOn'] = null;   
                    $portfolios[] = $getPortFolio;

                    $SharePortfolio = new SharePortfolio();
                    if(!is_null($SharePortfolio)){
                        $SharePortfolio->share_id = $CreateShare->id;
                        $SharePortfolio->portfolio_id = $portfolio_id;
                        $SharePortfolio->profile_id = $getPortFolio['profile_id'];
                        $SharePortfolio->save();
                    }
                }
                
            }


            // if(!empty($SharePortfolio->lastViewedOn)){
            //                             $PortFolio_diff = Carbon::parse($SharePortfolio->lastViewedOn)->diffForHumans();
            //                             $profiles['lastViewedOn'] = $PortFolio_diff;   
            //                         }else{
            //                             $profiles['lastViewedOn'] = null;   
            //                         }



            if(!empty($portfolios)){
                $CreateShare['profiles'] = $portfolios;
            }
            
            
            if(!empty($userRequest->clientContact)){
                $CreateShare['clientContact'] = $userRequest->clientContact;
                $clientEmails =  explode(",", $userRequest->clientContact);
                foreach($clientEmails as $clientContact){

                    $input['name'] = $userRequest->clientName;
                    $input['email'] = $userRequest->clientContact;
                    $input['user_role'] = 'client';
                    // if(!User::where('email', '=', $userRequest->clientContact)){
                    //     $NewClient = User::updateOrCreate($input);
                    // }

                    $UserCheck = User::where('email', '=', $userRequest->clientContact)->first();
                    if(empty($UserCheck)){
                        $NewClient = User::updateOrCreate($input);
                    }
                    

                    $CreateClient = new Client([
                        'share_id' => $CreateShare->id,
                        'clientEmail' => $clientContact,
                        'status' => $userRequest->status,
                        'createdBy' => $createdBy,
                        'updatedBy' => $updatedBy,
                    ]);

                    if($CreateClient->save()){
                        $clientAdded = true;
                        $CreateClientId = $CreateClient->id;
                    }
                    if($userRequest->EmailSent ==1){
                        Mail::to($clientContact)->send(new InvitationMail($UrlToShare));

                    }

                    $HistoryData['share_id'] = $CreateShare->id;
                    $HistoryData['client_id'] = $CreateClientId;
                    $HistoryData['description'] = "Contact created by ".ucwords($this->getAdminName($createdBy));
                    $HistoryData['activityType'] = "new_contact";
                    $HistoryData['loginType'] = "adminLogin";
                    $HistoryData['createdBy'] = $createdBy;
                    $HistoryData['updatedBy'] = $updatedBy;
                    $this->addHistory($HistoryData);
                }

                    if($userRequest->EmailSent ==1){

                        $HistoryData['share_id'] = $CreateShare->id;
                        $HistoryData['description'] = "Share URL shared by ".ucwords($this->getAdminName($createdBy))." to ". $userRequest->clientName." clients";
                        $HistoryData['activityType'] = "url_shared";
                        $HistoryData['loginType'] = "adminLogin";
                        $HistoryData['createdBy'] = $createdBy;
                        $HistoryData['updatedBy'] = $updatedBy;
                        $this->addHistory($HistoryData);


                        $HistoryData['share_id'] = $CreateShare->id;
                        $HistoryData['description'] = "Invitation sent to ".$userRequest->clientName." clients";
                        $HistoryData['activityType'] = "email_invitation";
                        $HistoryData['loginType'] = "adminLogin";
                        $HistoryData['createdBy'] = $createdBy;
                        $HistoryData['updatedBy'] = $updatedBy;
                        $this->addHistory($HistoryData);
                }
            }

            if($clientAdded){
                $response['message'] = 'Sharing Url generated Successfully along with the client';
            }else{
                $response['message'] = 'Sharing Url generated Successfully';
            }

            $response['status'] = true;            
            $response['result'] = $CreateShare; 
            // Mail::to($user->email, '$user->name')->send(new VerificationMail($user));
        }else{
            $response['status'] = false;
            $response['message'] = 'Error occurred';
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

    public function updateShareStatus(Request $request, $share_id){

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $loggedInUser = $request->user()->toArray();
        $createdBy = $loggedInUser['id'];
        $updatedBy = $loggedInUser['id'];

        if(!empty($share_id)){
            $getShare = Share::find($share_id);
            if(!empty($getShare)){
                $getShare->status = $userRequest->status;
                $getShare->updatedBy = $updatedBy;
                if($getShare->save()){
                    $response['status'] = true;
                    $response['share_state'] = $userRequest->status;
                    $response['message'] = "status updated";
                }else{
                    $response['status'] = false;
                    $response['message'] = "error occurred";
                }
            }else{
                $response['status'] = false;
                $response['message'] = "Invalid share id";
            }
        }else{
            $response['status'] = false;
            $response['message'] = "please provide valid inputs";
        }
        return $response;
    }

    function refreshShareUrl(Request $request, $share_id){

        $getShare = Share::find($share_id);
        if(empty($getShare)){
            $response['status'] = false;
            $response['message'] = 'Nothing found.';
        }else{
            $user = $request->user()->toArray();
            $updatedBy = $user['id'];
            $QueryStr = Str::random(15);
            // $UrlToShare = url('api/client/login/')."/".$QueryStr;
            // $UrlToShare = "http://localhost:3001/?key=".$QueryStr;
            $UrlToShare = "https://staging.dg13bjcoiqrz8.amplifyapp.com?key=".$QueryStr;

            // http://localhost:3001/?key=5Mvh16WUyK8VxWW
            
            $getShare->queryString = $QueryStr;
            $getShare->share_url = $UrlToShare;
            if($getShare->save()){
                $response['status'] = true;
                $response['message'] = 'Url Refreshed';
                $response['result'] = $getShare;

                $HistoryData['share_id'] = $share_id;
                $HistoryData['description'] = "Share url refreshed by ".ucwords($this->getAdminName($updatedBy));
                $HistoryData['activityType'] = "link_refreshed";
                $HistoryData['loginType'] = "adminLogin";
                $HistoryData['createdBy'] = $updatedBy;
                $HistoryData['updatedBy'] = $updatedBy;
                $this->addHistory($HistoryData);

            }
        }
        return $response;    
    }

    public function ClientShare($share_id){

        $getShare = Share::find($share_id);
        if(!empty($getShare)){
            $Ids = $getShare->profileShared;
            $ids = explode(',', $Ids);
            $portfolios = array();
            foreach($ids as $portfolio_id){

                $getPortFolio = $this->getPortFolio($portfolio_id, $share_id);
                $getResourceName = $this->getResourceName($getPortFolio['profile_id']);
                $getPortFolio['resource_name'] = $getResourceName['resource_name'];  
                $portfolios[] = $getPortFolio;
            }
            $getClients = Client::where('share_id', '=', $share_id)->get()->toArray();
            if(!empty($getClients)){
                $getShare['portfolios'] = $portfolios;
                $getShare['clients'] = $getClients;
                $response['status'] =true;
                $response['result'] =$getShare;
            }else{
                $response['status'] = false;
                $response['message'] = "No client found";
            }
        }else{
            $response['status'] = false;
            $response['message'] = "Invalid Share";
        }

        return $response;
    }

    public function portfolioShares($portfolio_id){

        $Portfolio = Portfolio::find($portfolio_id);
        if(!empty($Portfolio)){
            $Portfolio['portfolio_pdf_url'] = $this->getImageFromS3($portfolio_id, 'PortFolio');
            $profile = Profile::where('id', '=', $Portfolio->profile_id)->first()->toArray();
            $profile['image'] = $this->getImageFromS3($Portfolio->profile_id, "Profile");
            $Shares = Share::whereRaw("find_in_set('".$portfolio_id."',profileShared)")->get()->toArray();
            $Portfolio['profile'] = $profile;
            $Portfolio['Shares'] = $Shares;

            $response['status'] = true;
            $response['result'] = $Portfolio;
            $response['message'] = 'result found';
        }else{
            $response['status'] = false;
            $response['message'] = 'We couldn’t find that resource in our records. Try again.';;
        }

        return $response;
    }

    public function addNotes(Request $request, $shareId){

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $user = $request->user()->toArray();
        $updatedBy = $user['id'];

        $share = Share::find($shareId);
        if(!empty($share)){
            $share->notes = $userRequest->notes;
            $share->updatedBy = $updatedBy;
            // $Admin = Admin::find($userRequest->admin_id);
            // if(!empty($Admin)){

            if($share->save()){
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

    public function getNotes($shareId){
        $ShareNote = ShareNote::where('share_id','=',$shareId)->get()->toArray();
        if(!empty($ShareNote)){

            $response['status'] = true;
            $response['message'] = 'Notes found';
            $response['notes'] = $ShareNote;
        }else{
            $response['status'] = false;
            $response['message'] = 'No notes found';
        }

        return $response;
    }

    public function update(Request $request, $id)
    {
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $user = $request->user()->toArray();
        $updatedBy = $user['id'];


        $CreateShare = Share::find($id);
            if(!empty($CreateShare)){
                $dbSharedProfiles = $CreateShare->profileShared;
                $PostSharedProfiles = $userRequest->profileShared;

                //  $SharingUrl = SharingUrl::where('sharing_id', '=', $id)->first();
                // $SharingUrl->delete();

                $DbData = explode(',', trim($dbSharedProfiles));
                $PostData = explode(',', trim($PostSharedProfiles));


                foreach($DbData as $Previuos_ids){
                    if(!in_array($Previuos_ids, $PostData)){
                        $this->DecrementCount((int)$Previuos_ids, 'shares');
                        $SharePortfolio = SharePortfolio::where('share_id', $id)
                                                        ->where('portfolio_id', $Previuos_ids)
                                                        ->delete();
                                                // echo "<pre>";print_r($SharePortfolio);              
                        // if(!empty($SharePortfolio)){
                        //     $SharePortfolio->delete();
                        // }
                    }
                }

                foreach($PostData as $ids){
                    if(!in_array($ids, $DbData)){
                        $this->IncrementCount((int)$ids, 'shares');
                        $SharePortfolio = new SharePortfolio();
                        if(!is_null($SharePortfolio)){
                            if(!empty($this->getProfileIdByPortfolio($ids))){
                                $getProfileId = $this->getProfileIdByPortfolio($ids);
                                $SharePortfolio->share_id = $id;
                                $SharePortfolio->portfolio_id = $ids;
                                $SharePortfolio->profile_id = $getProfileId;
                                $SharePortfolio->save();
                            }
                            
                        }
                    }
                }

                $profileShared = $this->validateProfiles($userRequest->profileShared);

                if(!$profileShared || (empty($userRequest->profileShared))) {
                    $response['status'] = false;
                    $response['message'] = "Invalid Profiles shared. Please try again";
                    return $response;
                }


                $CreateShare->share_title = $userRequest->share_title;
                $CreateShare->project_title = $userRequest->project_title;
                $CreateShare->clientName = $userRequest->clientName;
                $CreateShare->profileShared = $userRequest->profileShared;
                $CreateShare->validity = $userRequest->validity;
                $CreateShare->notes = $userRequest->notes;
                $CreateShare->updatedBy = $updatedBy;

                if($CreateShare->save()){
                    $Ids = $CreateShare->profileShared;
                    $ids = explode(',', $Ids);
                    // print_r($ids);
                    // foreach($ids as $Portfolio_id){
                    //     $portFolio_id = (int)$Portfolio_id;
                    //     if(!is_int(trim($Portfolio_id))){
                    //         $response['status'] = false;
                    //         $response['message'] = "Sharing incorrect profile id ";
                    //         return $response;
                    //     }
                    // }
                    foreach($ids as $portfolio_id){
                        // if(is_int($portfolio_id)){
                            $getPortFolio = $this->getPortFolio($portfolio_id, $id);

                        if(!empty($portfolio_id)){
                            // $this->IncrementCount($portfolio_id, 'shares');
                            $getProfileIds= Portfolio::select('profile_id')->where('id', '=', $portfolio_id)->first();
                            if(!empty($getProfileIds)){
                                $getProfileIds = $getProfileIds->toArray();

                                $SharePortfolio = SharePortfolio::select('profile_id', 'lastViewedOn')
                                                        ->where('portfolio_id', $portfolio_id)
                                                        ->where('share_id', $id)
                                                        ->first();
                                                        // echo "<pre>";print_r($SharePortfolio->lastViewedOn);
                                                        // echo "<br>";
                                $profiles = Profile::find($getProfileIds['profile_id']);
                                if(!empty($profiles)){
                                    $profiles = $profiles->toArray();
                                    $profiles['profile_title'] = $this->getPortFolioTitle($portfolio_id);
                                    $profiles['image'] = $this->getImageFromS3($getProfileIds['profile_id'], "Profile");
                                    $profiles['portfolio_id'] = $portfolio_id;
                                    if(!empty($SharePortfolio->lastViewedOn)){
                                        $PortFolio_diff = Carbon::parse($SharePortfolio->lastViewedOn)->diffForHumans();
                                        $profiles['lastViewedOn'] = $PortFolio_diff;   
                                    }else{
                                        $profiles['lastViewedOn'] = null;   
                                    }
                                }
                            }
                            
                        }

                        $profilesArr[] = $profiles;
                        // }
                        
                        // $getResourceName = $this->getResourceName($getPortFolio['profile_id']);
                        // $getPortFolio['resource_name'] = $getResourceName['resource_name'];  
                        // $portfolios[] = $getPortFolio;
                    }

                    $CreateShare['profiles'] = $profilesArr;

                    $response['status'] = true;
                    $response['message'] = 'Sharing Url renewed Successfully';
                    $response['result'] = $CreateShare;
                    
                }else{
                    $response['status'] = false;
                    $response['message'] = 'Error occurred';
                }
            }else{
                $response['status'] = false;
                $response['message'] = 'No such share exist';
            }
            

        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function archiveShareRecord($id)
    {
        //
        $CreateShare = CreateShare::find($id);
        if($CreateShare){
            if($CreateShare->delete()){
                $SharingUrl = SharingUrl::where('sharing_id', '=', $id)->first();
                $SharingUrl->delete();
                $response['status'] = true;
                $response['message'] = 'Sharing Url deleted Successfully';

            }else{
                $response['status'] = false;
                    $response['message'] = 'Error occurred';
            }
            
        }else{
            $response['status'] = false;
                    $response['message'] = 'No such information available';
        }
        return $response;
    }


    public function deletePermanentlyShareRecord($id){
        $CreateShare = CreateShare::withTrashed()
                ->where('id', $id)
                ->first();

        if($CreateShare){
            if($CreateShare->forceDelete()){
                $SharingUrl = SharingUrl::withTrashed()->where('sharing_id', '=', $id)->first();
                $SharingUrl->forceDelete();
                $response['status'] = true;
                $response['message'] = 'Sharing Url deleted Successfully';

            }else{
                $response['status'] = false;
                    $response['message'] = 'Error occurred';
            }
            
        }else{
            $response['status'] = false;
                    $response['message'] = 'No such information available';
        }
        return $response;
    }

    public function sharing(Request $request)
    {
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));
        
        $client_id = 5;
        // url('/client/login/').$QueryStr;
        $UrlAccess = 'Public';
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
        }else{
            $response['status'] = false;
            $response['message'] = 'Error occurred';
        }

        return $response;
    }
}
