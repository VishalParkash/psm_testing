<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Profile;
use App\Share;
use App\Client;
use App\Portfolio;
use App\SharePortfolio;
use App\Http\Traits\CommonTrait;
use Carbon\Carbon;

class DashboardController extends Controller
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


    public function index(){
        // $getProfiles = CreateShare::where('clientGoogle_email', '=', $id)->get()->toArray();
        // $Shares = Share::all();
        $Shares = Share::get();
        // $Shares = DB::Share('posts')->get();
        // print_r($Shares);die;
        if(!empty($Shares)) {
            $Profiles = array();
            $getPortfolio = array();
            $ProfileIds = array();
            $ProfileDetails = array();
            $getClients = array();
            $clientEmail = '';
            $count = 0;
        foreach($Shares as $share){
            $Ids = $share['profileShared'];
            $ids = explode(',', $Ids);

            $profiles= array();
            $ProfileArr= array();
            if(!empty($ids)){
                // $profiles= array();
                    $ProfileArr= array();
                foreach ($ids as $portfolio_id) {
                    if(!empty($portfolio_id)){

                        $SharePortfolio = SharePortfolio::select('profile_id', 'lastViewedOn')
                                                        ->where('portfolio_id', $portfolio_id)
                                                        ->where('share_id', $share['id'])
                                                        ->first();
                        if(!empty($SharePortfolio)){
                            $SharePortfolio = $SharePortfolio->toArray();
                            // echo "<pre>";print_r($getProfileIds);
                            $profiles = Profile::where('id', '=', $SharePortfolio['profile_id'])->first();
                            // echo "<pre>"; print_r($profiles);
                            if(!empty($profiles)){
                                $profiles = $profiles->toArray();
                                $profiles['image'] = $this->getImageFromS3($SharePortfolio['profile_id'], 'Profile');
                                $profiles['portfolio_id'] = $portfolio_id;
                                

                                if(!empty($SharePortfolio['lastViewedOn'])){

                                    $PortFolio_diff = Carbon::parse($SharePortfolio['lastViewedOn'])->diffForHumans();
                                    $profiles['lastViewedOn'] = $PortFolio_diff;   
                                }
                            }
                            
                        }
                    
                    }   
                        if(!empty($profiles)){
                            $ProfileArr[] = $profiles;    
                        }
                        
                }
            }

            $getClients = Client::select('clientEmail')->where('share_id', '=', $share['id'])->get()->toArray();
            $clientEmail = '';
            foreach($getClients as $client){
                $clientEmail .= $client['clientEmail'].",";

            }

            if(!empty($share['lastViewedOn'])){
                $diff = Carbon::parse($share['lastViewedOn'])->diffForHumans();
                $share['lastViewedOn'] = $diff;    
            }
            
            $share['clientContact'] = rtrim($clientEmail, ",");
            if(!empty($ProfileArr)){
                        $share['profiles'] = $ProfileArr;
                    }else{
                        $share['profiles'] = array();
                    }
            
            $ShareDetails[] = $share;
        }
                if(!empty($ShareDetails)){
                    $response['status'] = true;
                    $response['result'] = $ShareDetails;
                    $response['message'] = 'Valid records';
                }else{
                    $response['status'] = false;
                    $response['message'] = 'No records found';
                }
                
        }else{
            $response['status'] = false;
            $response['message'] = 'Invalid user';
        }
        


        return $response; 
    }
}
