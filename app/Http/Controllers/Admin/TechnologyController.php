<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Technology;

class TechnologyController extends Controller
{	
	use CommonTrait;
    public function index(){
    	$Technology = Technology::all();
    	$theTechnlogy = array();
    	if(!empty($Technology)){
	    	foreach($Technology as $Technologies){
	    		$Technologies->icon = $this->getImageFromS3($Technologies->id, "Icon");
	    		$theTechnlogy[] = $Technologies;
	    	}
	    	$response['status'] = true;
	    	$response['result'] = $theTechnlogy;
    	}else{
    		$response['status'] = true;
	    	$response['message'] = "No icon available";
    	}
    	return $response;
    }

    public function addTechnology(){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        // echo "<pre>";print_r($userRequest);die;
        foreach($userRequest as $technologies){
        	$Technology = new Technology();
        if(!is_null($Technology)){
        	$Technology->technology = $technologies->technology;
        	$Technology->icon = $technologies->icon;
        	if($Technology->save()){
        		$response['status'] = true;
        		$response['message'] = "icon added";
        	}
        }
        }
        
    }

    public function uploadIcon(Request $request){
        $requestData = trim(file_get_contents("php://input"));
        $requestData = rtrim($requestData, ":");
        $userRequest = (json_decode($requestData, true));
        $getImageData = $userRequest['file'];

        $response = $this->uploadFile($getImageData, 'icon');
        return $response;

    }
}
