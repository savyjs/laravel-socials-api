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

Route::middleware('auth:api')->namespace('Telegram')->group(function () {
    Route::any('/telegram/send-message', 'TelegramController@sendMessage');
});

Route::middleware('auth:api')->namespace('Twitter')->group(function () {
    // google token
    Route::any('/twitter/revoke-token', 'TwitterController@revokeTwitterAccess');
    Route::any('/twitter/check-token/{uid}', 'TwitterController@checkTwitterAccess');
    Route::any('/twitter/get-token', 'TwitterController@getTwitterAccessToken');
    Route::any('/twitter/get-profile', 'TwitterController@getTwitterProfile');
    Route::any('/twitter/get-profile-or-auth-link', 'TwitterController@getTwitterProfileOrAuthLink');
    Route::any('/twitter/auto-refresh', 'TwitterController@twitterRefreshAllTokens');
    Route::any('/twitter/send-tweet', 'TwitterController@sendTweet');
});

Route::middleware('auth:api')->namespace('Google')->group(function () {
    // google token
    Route::any('/google/revoke-token/{provider}', 'AuthController@revokeGoogleAccess');
    Route::any('/google/check-token/{provider}/{uid}', 'AuthController@checkGoogleAccess');
    Route::any('/google/get-token/{provider}', 'AuthController@getGoogleAccessToken');
    Route::any('/google/get-profile/{provider}', 'AuthController@getGoogleProfile');
    Route::any('/google/get-profile-or-auth-link/{provider}', 'AuthController@getGoogleProfileOrAuthLink');

    // youtube routes
    Route::any('/youtube/get-channels-list', 'YoutubeController@youtubeGetChannels');
    Route::any('/youtube/insert-video', 'YoutubeController@youtubeInsertVideo');

});
Route::any('/google/auto-refresh', 'Google\AuthController@googleRefreshAllTokens');


Route::post('login', 'PassportController@login');
Route::post('register', 'PassportController@register');

Route::middleware('auth:api')->group(function () {
    Route::get('user', 'PassportController@details');
});
