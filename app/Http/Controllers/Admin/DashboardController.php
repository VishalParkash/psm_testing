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
            // print_r($ids);die;
            // $ProfileIds= array();
            $profiles= array();
            $ProfileArr= array();
            if(!empty($ids)){
                // $profiles= array();
                $temp= array();
            foreach ($ids as $portfolio_id) {
                # code...
                // echo $portfolio_id;
                // echo "portfolio ids <br>";
                if(!empty($portfolio_id)){

                    $getProfileIds= Portfolio::select('profile_id')->where('id', '=', $portfolio_id)->first()->toArray();
                    // $profiles = Profile::where('id', '=', $getProfileIds['profile_id'])->first()->toArray();
                    // $getPortfoilio = Portfolio::where('id', '=', $portfolio_id)->first()->toArray();
                    // $profiles['portfolio'][] = $getPortfoilio;
                    // echo "<br>";
                    // echo $getProfileIds['profile_id'];
                    // echo "<br>";

                        // if(!in_array($getProfileIds['profile_id'], $ProfileIds)){
                            // $count = 1;
                            // array_push($ProfileIds, $getProfileIds['profile_id']);
                            // foreach($ProfileIds as $ProfileId){
                    if(!empty($getProfileIds)){
                                   $profiles = Profile::where('id', '=', $getProfileIds['profile_id'])->first()->toArray();
                                   $profiles['image'] = $this->getImageFromS3($getProfileIds['profile_id'], 'Profile');
                                $profiles['portfolio_id'] = $portfolio_id;
                    }
                     
                                // echo "<pre>";print_r($profiles);
                               
                        // }else{
                        //     $count = 0;
                        //     $temp = array();
                        // }
                        
                
            }
            $ProfileArr[] = $profiles;    
            // $ProfileArr[] = $profiles;
                // if(($count==1)){
                //     $ProfileArr[] = $profiles;    
                // }else{
                //     $ProfileArr[] = $temp;
                // }
                
            }
            }


            $getClients = Client::select('clientEmail')->where('share_id', '=', $share['id'])->get()->toArray();
            // echo "<pre>";print_r($getClients);
            $clientEmail = '';
            foreach($getClients as $client){
                $clientEmail .= $client['clientEmail'].",";

            }
            // $getClients = implode(",", $getClient['clientEmail']);



            $diff = Carbon::parse($share['lastViewedOn'])->diffForHumans();
            $share['lastViewedOn'] = $diff;
            $share['clientContact'] = rtrim($clientEmail, ",");
            $share['profiles'] = $ProfileArr;
            $ShareDetails[] = $share;
        }
                if(!empty($ShareDetails)){
                    $response['status'] = true;
                    $response['details'] = $ShareDetails;
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


        public function index1111(){
        // $getProfiles = CreateShare::where('clientGoogle_email', '=', $id)->get()->toArray();
        $Shares = Share::all();
        // echo "<pre>";print_r($getProfiles);die;
        if(!empty($Shares)){
            $Profiles = array();
            $getPortfolio = array();
            $ProfileIds = array();
            $ProfileDetails = array();
            $count = 0;
        foreach($Shares as $share){
            $Ids = $share['profileShared'];
            $ids = explode(',', $Ids);
            if(!empty($ids)){
                $ProfileArr= array();
                $temp= array();
            foreach ($ids as $portfolio_id) {
                # code...
                if(!empty($portfolio_id)){
                    $getProfileIds= Portfolio::select('profile_id')->where('id', '=', $portfolio_id)->first()->toArray();
                        
                        if(!in_array($getProfileIds['profile_id'], $ProfileIds)){
                            array_push($ProfileIds, $getProfileIds['profile_id']); 
                        }
                        $ProfileIds[] =$ProfileIds;

            }
            $ProfileArr[] = $portfolio_id;
            }
            }


            $share['profiles'] = $ProfileArr;
            $ShareDetails[] = $share;
        }

                $response['status'] = true;
                $response['details'] = $ShareDetails;
                $response['message'] = 'Valid records';
        }else{
            $response['status'] = false;
            $response['message'] = 'Invalid user';
        }
        


        // echo "<pre>";print_r($ProfileDetails);die;
        return $response; 
        // echo "<pre>";print_r($getShares);
    }
}
