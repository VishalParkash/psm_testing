<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\RecentActivity;

class RecentActivityController extends Controller
{
    //

    public function index(){

    	$RecentActivity = RecentActivity::where('share_id', '=', $share_id)->get()->toArray();
    	if(!empty($RecentActivity)){
    		$response['status'] = true;
    		$response['activities'] = $RecentActivity;
    		$response['message'] = 'Records found';
    	}else{
    		$response['status'] = false;
    		$response['message'] = 'No activities available for this share id';
    	}

    	return $response;
    }
}
