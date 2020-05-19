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


// auth and token requests


Route::middleware('auth:api')->namespace('Google')->group(function () {
    // google token
    Route::get('/google/check-token/{provider}/{uid}', 'AuthController@checkGoogleAccess');
    Route::get('/google/get-token/{provider}', 'AuthController@getGoogleAccessToken');
    Route::get('/google/get-profile/{provider}', 'AuthController@getGoogleProfile');
    Route::get('/google/get-profile-or-auth-link/{provider}', 'AuthController@getGoogleProfileOrAuthLink');
    Route::get('/google/auto-refresh', 'AuthController@googleRefreshAllTokens');

    // youtube routes
    Route::get('/youtube/get-channels-list/{uid}', 'Google\YoutubeController@youtubeGetChannels');
    Route::get('/youtube/insert-video/{uid}', 'Google\YoutubeController@youtubeInsertVideo');
});



Route::post('login', 'PassportController@login');
Route::post('register', 'PassportController@register');

Route::middleware('auth:api')->group(function () {
    Route::get('user', 'PassportController@details');
});
