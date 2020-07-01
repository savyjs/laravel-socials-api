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

Route::middleware('auth:api')->namespace('Google')->group(function () {
    // google token
    Route::any('/google/revoke-token/{provider}', 'AuthController@revokeGoogleAccess');
    Route::any('/google/check-token/{provider}/{uid}', 'AuthController@checkGoogleAccess');
    Route::any('/google/get-token/{provider}', 'AuthController@getGoogleAccessToken');
    Route::any('/google/get-profile/{provider}', 'AuthController@getGoogleProfile');
    Route::any('/google/get-profile-or-auth-link/{provider}', 'AuthController@getGoogleProfileOrAuthLink');
    Route::any('/google/auto-refresh', 'AuthController@googleRefreshAllTokens');

    // youtube routes
    Route::any('/youtube/get-channels-list', 'YoutubeController@youtubeGetChannels');
    Route::any('/youtube/insert-video', 'YoutubeController@youtubeInsertVideo');

});

Route::post('login', 'PassportController@login');
Route::post('register', 'PassportController@register');

Route::middleware('auth:api')->group(function () {
    Route::get('user', 'PassportController@details');
});
