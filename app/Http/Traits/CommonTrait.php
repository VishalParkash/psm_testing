<?php 
namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;
use Jenssegers\Agent\Agent as Agent;
use App\Profile;
use App\Share;
use App\History;
use App\Portfolio;
use App\User;
use App\SharePortfolio;

use Carbon\Carbon;
use Illuminate\Support\Str;


trait CommonTrait	{


    // function uploadFileToS3($fileType, $file, $resource){

    // }

	function getImageFromS3($profile_id, $userType){

        if($userType == 'Profile'){
            $getProfileImageName = Profile::select('image')->where("id","=",$profile_id)->first()->toArray();
            $File = $getProfileImageName['image'];
            $key = "profile/".$File;
        }elseif ($userType == 'Portfolio') {
            $getProfileImageName = Portfolio::select('portfolio_pdf_url')->where("id","=",$profile_id)->first()->toArray();
            $File = $getProfileImageName['portfolio_pdf_url'];
            $key = "pdf/".$File;
        }
		
        if(empty($File)){
            return null;
        }
		$BucketName = 'profile-sharing-app';
        // $key = "profile/".$image;

		$s3 = \Storage::disk('s3');
        if (!$s3->exists($key)) {
            return null;
            // $response['status'] = false;
            // $response['message'] = 'Invalid image';
        }else{
            $client = $s3->getDriver()->getAdapter()->getClient();
            $command = $client->getCommand('GetObject', [
                'Bucket' => $BucketName,
                'Key'    => $key
            ]);

            if ($userType == 'Profile') {
                $expiry = "+10 minutes";
            }

            
            $request = $client->createPresignedRequest($command, $expiry);
            return $imageUrl =  (string) $request->getUri();
        }
        return $response;
	}

    function updateProfileStatus(Request $request, $profile_id){
        $profile = Profile::find($profile_id);

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $updatedBy = $userRequest->updatedBy;


        if(!empty($profile)){
            $profile->status = $userRequest->status;
            $profile->updatedBy = $updatedBy;
            if($profile->save()){
                return true;
            }else{
                return false;
            }
        }
    }

    function extendTheValidity($validityType, $Id, $new_date, $userId){

        if($validityType== 'Share'){
            $HistoryData['share_id'] = $Id;
            $HistoryData['activityType'] = "share_validity_extended";

            $Table = Share::find($Id);
        }elseif($validityType == 'Portfolio'){
            $HistoryData['activityType'] = "portfolio_validity_extended";
            $Table = Portfolio::find($Id);

        }elseif($validityType == ''){
            $response['status'] = false;
            $response['message'] = 'Invalid Parameters. Provide validity type.';
            return $response;
        }elseif($new_date == ''){
            $response['status'] = false;
            $response['message'] = 'Invalid Parameters. Provide valid date.';
            return $response;
        }else{
            $response['status'] = false;
            $response['message'] = 'Invalid Parameters.';
            return $response;
        }

        // $Table = $table::find($Id);

        if(!empty($Table)){
            $existing_validity = $Table->validity;
            $newValidity = Carbon::createFromTimestampUTC($new_date)->toDateTimeString();
            

            if($newValidity < $existing_validity){
                $response['status'] = false;
                $response['message'] = "extended date cannot be less than existing validity";
            }else{
                $extendedValidity = strtotime($existing_validity);
                $extendedValidity = strtotime("+7 day", $extendedValidity);
                $extendedValidity =  date('Y-m-d', $extendedValidity);

                $Table->validity = $extendedValidity;
                $Table->updatedBy = $userId;
                if($Table->save()){

                    
                    
                    $HistoryData['description'] = $validityType." validity date extended till ".$extendedValidity." by ".ucwords($this->getAdminName($userId));
                    
                    $HistoryData['loginType'] = "adminLogin";
                    $HistoryData['createdBy'] = $userId;
                    $HistoryData['updatedBy'] = $userId;
                    $this->addHistory($HistoryData);


                    $response['status'] = true;
                    $response['message'] = "validatity extended till ".$extendedValidity;
                }else{
                    $response['status'] = false;
                    $response['message'] = "Error occurred";
                }
            }

            
        }else{
            $response['status'] = false;
            $response['message'] = "No such ".$validityType." exists";
        }
        return $response;
    }
	
    function getPortFolio($portfolio_id, $share_id=false){
        if(!empty(($portfolio_id))){
            $Portfolio = Portfolio::find($portfolio_id);
            if(!empty($Portfolio)){
                $Portfolio = $Portfolio->toArray();
                $SharePortfolio = SharePortfolio::select('lastViewedOn')
                                                        ->where('portfolio_id', $portfolio_id)
                                                        ->where('share_id', $share_id)
                                                        ->first();

                if(!empty($SharePortfolio)){
                    $SharePortfolio = $SharePortfolio->toArray();
                    if(!empty($SharePortfolio['lastViewedOn'])){
                        $diff = Carbon::parse($SharePortfolio['lastViewedOn'])->diffForHumans();
                        $Portfolio['lastViewedOn'] = $diff;    
                    }
                }
                
                return $Portfolio;
            }
        }else{
            return false;
        }
    }

    function validateProfiles($portfolios){
        if(!empty($portfolios)){
            $getPortFolioIds = explode(',', $portfolios);
            foreach($getPortFolioIds as $PortfolioId){
                $Portfolio = Portfolio::select('profile_id')->where('id', $PortfolioId)->first();                    
                if(!empty($Portfolio)){
                    $getProfile = Profile::find($Portfolio->profile_id);
                    if(empty($getProfile)){
                        return false;
                    }
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        return true;
    }

    function getShareTitle($share_id){
        $Share = Share::select('share_title')->where('id', '=', $share_id)->first();
        if(!empty($Share)){
            return $Share->share_title;
        }
        
    }

    function getProfile($profile_id){
        $Profile = Profile::find($profile_id)->toArray();
        return $Profile;
    }

    function getProfileIdByPortfolio($portfolio_id){
        $Profile = Portfolio::select('profile_id')->find($portfolio_id);
        if(!empty($Profile)){
            $Profile = $Profile->toArray();
        }
        return $Profile['profile_id'];
    }

    function getResourceName($profile_id){
        $Profile = Profile::select('resource_name')->find($profile_id);
        if(!empty($Profile)){
            $Profile = $Profile->toArray();
        }
        return $Profile;
    }

    function getAdminName($admin_id){
        $Admin = User::select('name')->where("id", "=", $admin_id)
                            // ->where("user_role", "=", "admin")
                            ->where("status", "=", 1)
                            ->first()
                            ->toArray();
        if(!empty($Admin)){
            return $Admin['name'];    
        }
    }

    function getClientName($client){
        $Client = User::select('name')->where("id", "=", $client)
                            // ->where("user_role", "=", "client")
                            ->where("status", "=", 1)
                            ->first();
                            
        if(!empty($Client)){
            $Client = $Client->toArray();
            return $Client['name'];    
        }
    }

    function getDeviceType(){
        $Agent = new Agent();
        if ($Agent->isMobile()) {
                    // you're a mobile device
                    // $resetPassword['resetPasswordUrl'] = 'expenserocket://api/password/token/'.$passwordReset->token;
            $getDeviceType = "Mobile";
        } else {
            // you're a desktop device, or something similar
            $getDeviceType = "Desktop";
        }
        return $getDeviceType;
    }

    function addHistory($data){
        $loginTime = Carbon::now();

        $History = new History();
        if(!empty($data['share_id'])){
            $History->share_id = $data['share_id'];    
        }
        if(!empty($data['client_id'])){
            $History->client_id = $data['client_id'];    
        }
        
        $History->description = $data['description'];
        $History->activityType = $data['activityType'];
        $History->loginTime = $loginTime->toDateTimeString();
        $History->loginType = $data['loginType'];
        $History->deviceType = $this->getDeviceType();
        $History->createdBy = $data['createdBy'];
        $History->updatedBy = $data['updatedBy'];
        $History->save();
        return true;
    }

    public function vaidationProfileUrl($profileUrl){
        if ($profileUrl == NULL){
            $response['status'] = false;
            $response['message'] = 'null data';
            return $response;
        }
        $ch = curl_init($profileUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpcode >= 200 && $httpcode < 300) ? true : false; 
    }

    public function IncrementCount($portfolio_id, $IncrementType){
        if(!empty($portfolio_id)){
            $Portfolio = Portfolio::find($portfolio_id);
            if(!empty($Portfolio)){
                // $Portfolio->increment('views');
                $Portfolio->$IncrementType++;
                // if($Portfolio->increment($IncrementType)) {
                if($Portfolio->save()) {
                    $response['status'] = true;
                }else{
                    $response['status'] = false;
                }
            }else{
                $response['status'] = false;
            }
        }else{
            $response['status'] = false;
        }
        return $response;
    }

        public function DecrementCount($portfolio_id, $IncrementType){
        if(!empty($portfolio_id)){
            $Portfolio = Portfolio::find($portfolio_id);
            if(!empty($Portfolio)){
                // $Portfolio->increment('views');
                $Portfolio->$IncrementType--;
                // if($Portfolio->increment($IncrementType)) {
                if($Portfolio->save()) {
                    $response['status'] = true;
                }else{
                    $response['status'] = false;
                }
            }else{
                $response['status'] = false;
            }
        }else{
            $response['status'] = false;
        }
        return $response;
    }

}