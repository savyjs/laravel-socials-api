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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// proxy for all sites 
Route::post('/callApi/request', 'General@callExternalApi');

// proxy for google 
Route::post('/google/request', 'ApiController@googleApi');

// youtube 
Route::post('/youtube/request',   'ApiController@youtubeApi');


// telegram 
Route::post('/telegram/request', function (Request $request) {
    // proxy request
});


// twitter 
Route::post('/twitter/request', function (Request $request) {
    // proxy request
});


// facebook 
Route::post('/facebook/request', function (Request $request) {
    // proxy request
});


// linkedin
Route::post('/linkedin/request', function (Request $request) {
    // proxy request
});


// aparat
Route::post('/aparat/request', function (Request $request) {
    // proxy request
});


// soundcloud
Route::post('/soundcloud/request', function (Request $request) {
    // proxy request
});

// instagram
Route::post('/instagram/request', function (Request $request) {
    // proxy request
});

// pinterest
Route::post('/pinterest/request', function (Request $request) {
    // proxy request
});

