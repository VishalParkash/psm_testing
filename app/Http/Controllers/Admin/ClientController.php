<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Client;
use App\Share;
use Validator;
use App\Http\Traits\CommonTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    //
    use CommonTrait;

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


   	public function create(Request $request){
   		$requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        // echo "<pre>";print_r($userRequest);die;

        $loggedInUser = $request->user()->toArray();
        $createdBy = $loggedInUser['id'];
        $updatedBy = $loggedInUser['id'];

        // $validator = Validator::make($userRequestValidate, [
        //     'clientEmail' => 'required|string|email|unique:clients',
        //     // 'name' => 'required|string',
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

        $ValidateShare = Share::find($userRequest->share_id);
        if(!empty($ValidateShare)){
        	$UrlToShare = $ValidateShare->share_url;
        	$CreateClient = new Client([
            'share_id' => $userRequest->share_id,
            'clientName' => $userRequest->clientName,
            'client_title' => $userRequest->client_title,
            'clientEmail' => $userRequest->clientEmail,
            'clientPhone' => $userRequest->clientPhone,
            'Skype_Slack_id' => $userRequest->Skype_Slack_id,
            'notes' => $userRequest->notes,
            'leadSource' => $userRequest->leadSource,
            'millipixelsRep' => $userRequest->millipixelsRep,
            'invitationSent' => $userRequest->invitationSent,
            'status' => $userRequest->status,
            'createdBy' => $createdBy,
            'updatedBy' => $updatedBy,
        ]);

	        if($CreateClient->save()){
	        	if($userRequest->invitationSent ==1){
                    Mail::to($userRequest->clientEmail)->send(new InvitationMail($UrlToShare));
                    if($userRequest->invitationSent ==1){
		                $HistoryData['share_id'] = $userRequest->share_id;
		                $HistoryData['client_id'] = $CreateClient->id;
		                $HistoryData['description'] = "Invitation sent to ".$userRequest->clientName;
		                $HistoryData['activityType'] = "email_invitation";
		                $HistoryData['loginType'] = "adminLogin";
		                $HistoryData['createdBy'] = $createdBy;
		                $HistoryData['updatedBy'] = $updatedBy;
		                $this->addHistory($HistoryData);
		            }

                }
	        $HistoryData['share_id'] = $userRequest->share_id;
            $HistoryData['client_id'] = $CreateClient->id;
            $HistoryData['description'] = "Contact created by ".ucwords($this->getAdminName($createdBy));
            $HistoryData['activityType'] = "new_contact";
            $HistoryData['loginType'] = "adminLogin";
            $HistoryData['createdBy'] = $createdBy;
            $HistoryData['updatedBy'] = $updatedBy;
            $this->addHistory($HistoryData);

	        	$getClients = Client::all()->toArray();
	        	$CreateClient['clients'] = $getClients;

	            $response['status'] = true;
	            $response['message'] = 'Client created Successfully';
	            $response['user'] = $CreateClient; 
	            // die('here');
	            // try {
	    // Mail::to($user->email, '$user->name')->send(new VerificationMail($user));            
	        }else{
	            $response['status'] = false;
	            $response['message'] = 'Error occurred';
	        }
        }else{
        	$response['status'] = false;
	        $response['message'] = 'Invalid Share';
        }
        return $response;
   	}

   	public function update(Request $request, $client_id){
		$requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $loggedInUser = $request->user()->toArray();
        $updatedBy = $loggedInUser['id'];

        $ValidateShare = Share::find($userRequest->share_id);
        if(!empty($ValidateShare)){

        	$getClient = Client::find($client_id);
        	if(!empty($getClient)){
	        	$getClient->share_id = $userRequest->share_id;
	            $getClient->clientName = $userRequest->clientName;
	            $getClient->client_title = $userRequest->client_title;
	            $getClient->clientEmail = $userRequest->clientEmail;
	            $getClient->clientPhone = $userRequest->clientPhone;
	            $getClient->Skype_Slack_id = $userRequest->Skype_Slack_id;
	            $getClient->notes = $userRequest->notes;
	            $getClient->leadSource = $userRequest->leadSource;
	            $getClient->millipixelsRep = $userRequest->millipixelsRep;
	            $getClient->invitationSent = $userRequest->invitationSent;
	            $getClient->status = $userRequest->status;
	            $getClient->updatedBy = $updatedBy;

	            if($getClient->save()){
	            	$getClients = Client::all()->toArray();
	        		$getClient['clients'] = $getClients;
	            	$response['status'] = true;
		            $response['message'] = 'Client created Successfully';
		            $response['user'] = $getClient;
	            }else{
		            $response['status'] = false;
		            $response['message'] = 'Error occurred';
	       		}
        	}else{
	        	$response['status'] = false;
		        $response['message'] = 'Invalid client id';
        	}
   		}else{
        	$response['status'] = false;
	        $response['message'] = 'Invalid Share id';
        }
        return $response;
   }

    public function getClient($client_id){
    	$getClient = Client::find($client_id)->toArray();
    	if(!empty($getClient)){
    		// echo $getClient['share_id'];die;
    		$getShare = Share::find($getClient['share_id'])->toArray();
    		$getClient['share'] = $getShare;
    		
    		$response['status'] = true;
    		$response['result'] = $getClient;
    		$response['message'] = "valid result";
    	}else{
    		$response['status'] = false;
    		$response['message'] = "No client found";
    	}

    	return $response;
    }
}
