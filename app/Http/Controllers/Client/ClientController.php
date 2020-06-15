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

    public function login(Request $request, $queryString){
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));
        // $Portfolio = array();
        if(empty($queryString)){
            $response['status'] = false;
            $response['message'] = "Invalid login Url.";
            return $response;
        }
        if(!empty($userRequest)){

            $getShare = Share::where('queryString','=', $queryString)
                                ->where('status', 1)
                                ->first();
                                // echo "<pre>";print_r($getShare);die;
            if(!empty($getShare)){

                $getShareValidityDate = $getShare->validity;
                
                if(Carbon::now() > $getShareValidityDate){
                    // die('here');
                    $response['status'] = false;
                    $response['message'] = "Url Expired.";
                    return $response;
                }

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
                            if(!empty($Portfolio)){
                                $ProfileArr[] = $Portfolio;
                            }
                            
                        }
                        $getShare->lastViewedOn = Carbon::now();
                        $getShare->save();
                    }
                    if(!empty($ProfileArr)){
                        $getClient['profiles'] = $ProfileArr;
                    }else{
                        $getClient['profiles'] = array();
                    }
                    
                    $response['status'] = true;
                    $response['token'] =  $user->createToken('ProfileSharingApp-client')->accessToken; 
                    $response['result'] = $getClient;
                    $response['message'] = "valid result";
                }

                
            }else{
                $response['status'] = false;
                $response['message'] = 'It seems to be an invalid share. Please try again';
            }
        }else{
            $response['status'] = false;
            $response['message'] = 'Invalid inputs';
        }

        return $response;

    }
}
