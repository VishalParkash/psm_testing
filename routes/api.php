<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('/admin')->name('admin.')->group(function(){
	Route::post('register', 'Admin\AdminController@register');
	Route::post('login', 'Admin\AdminController@login')->name('login');
	// Route::post('/user' , 'Admin\UserController@store');
	Route::post('assistance', 'Admin\UserController@assistance');
    Route::post('technology/add', 'Admin\TechnologyController@addTechnology');
    Route::post('technology/icon', 'Admin\TechnologyController@uploadIcon');
    Route::get('technologies', 'Admin\TechnologyController@index');
	

	Route::group([
      'middleware' => 'auth:api'
    ], function() {
    	
    	//Profile Resources and Portfolios
        Route::post('profile/add' , 'Admin\UserController@store')->name('add');
        Route::post('profile/create' , 'Admin\UserController@createProfile');
        Route::post('/upload' , 'Admin\UserController@upload');
		Route::get('profile/{id}' , 'Admin\UserController@show');
		Route::get('profiles/' , 'Admin\UserController@list');
		Route::post('profile/{id}' , 'Admin\UserController@update');
		Route::post('profile/edit/{id}' , 'Admin\UserController@updateProfile');
		Route::post('profile/status/{id}' , 'Admin\UserController@updateProfileStatus');
        Route::post('profile/gallery/upload/' , 'Admin\UserController@uploadGallery');
        Route::get('profile/gallery/{id}' , 'Admin\UserController@getGallery');
        // Route::post('profile/gallery/add/{id}' , 'Admin\UserController@addGallery');

		Route::post('portfolio/add/{profile_id}' , 'Admin\UserController@portfolio');
		Route::post('portfolio/update/{portfolio_id}' , 'Admin\UserController@updatePortfolio');
		Route::post('portfolio/status/{id}' , 'Admin\UserController@updatePortfolioStatus');
		Route::post('portfolio/notes/{portfolio_id}' , 'Admin\UserController@addNotes');
		Route::get('skills', 'Admin\UserController@skills');
		Route::post('skill/profiles', 'Admin\UserController@skilledProfiles');
		Route::post('validate/url', 'Admin\UserController@checkUrlValidity');

		//Shares
		Route::post('share/add' , 'Admin\ShareController@create');
		Route::post('share/{id}' , 'Admin\ShareController@update');
		Route::post('validity/{id}' , 'Admin\ShareController@extendValidity');
		Route::post('refresh/{share_id}' , 'Admin\ShareController@refreshShareUrl');
		Route::get('share/archive/{id}' , 'Admin\ShareController@archiveShareRecord');
		Route::get('share/delete/{id}' , 'Admin\ShareController@deletePermanentlyShareRecord');
		Route::get('share/list/' , 'Admin\ShareController@list');
		Route::post('share/notes/{share_id}' , 'Admin\ShareController@addNotes');
		Route::get('clientshare/{share_id}/' , 'Admin\ShareController@ClientShare');
		Route::get('profiles/{portfolio_id}/' , 'Admin\ShareController@portfolioShares');
		Route::post('share/status/{id}' , 'Admin\ShareController@updateShareStatus');
		
        //Projects
        Route::post('project' , 'Admin\ProjectController@create');
        Route::post('project/{project}' , 'Admin\ProjectController@create');
        // Route::post('project/{project}' , 'Admin\ProjectController@update');
        Route::get('project/{project}' , 'Admin\ProjectController@project');
        Route::get('projects' , 'Admin\ProjectController@projects');
        Route::post('image/project/' , 'Admin\ProjectController@uploadProjectImage');

		//Client
		Route::post('client/add' , 'Admin\ClientController@create');
		Route::get('client/{client_id}' , 'Admin\ClientController@getClient');
		Route::post('client/update/{client_id}' , 'Admin\ClientController@update');
		
		


		//Dashboard
		Route::get('dashboard' , 'Admin\DashboardController@index');

		//History
		Route::get('history/{HistoryType}/{TypeId}' , 'Admin\HistoryController@index');
    });
});


Route::group(['middleware' => ['web']], function () {
    // your routes here
	Route::get('social/{SocialProvider}', 'Client\SocialController@redirectToProvider');
	Route::get('social/{SocialProvider}/callback', 'Client\SocialController@handleProviderCallback');
});

Route::post('client/login/{qryString}' , 'Client\ClientController@login');
Route::get('client/social/auth' , 'Client\SocialController@linkedinAuth');
Route::get('client/social/access' , 'Client\SocialController@linkedinAccess');
	Route::group([
      'middleware' => 'auth:api'
    ], function() {
    	Route::get('/client/dashboard/{share_id}', 'Client\ProfileController@profiles');
    	Route::get('/portfolio/views/{portfolio_id}/{share_id}', 'Client\ProfileController@countPortfolioViews');
    	Route::post('client/profiles' , 'Client\ProfileController@profiles');
      Route::get('client/validate/{UpdatedAt}/{share_id}' , 'Client\ProfileController@getChange');
    });
	// Route::post('/client/share', 'Client\ClientController@create');