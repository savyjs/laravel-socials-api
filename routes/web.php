<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


// auth for google
Route::get('/google/auth/{provider}', 'AuthController@googleAuth');
Route::get('/google/back/{provider}', 'AuthController@googleAuthBack');
Route::get('/google/get-token/{provider}', 'AuthController@getGoogleAccess');
Route::get('/google/check-token/{provider}/{uid}', 'AuthController@checkGoogleAccess');
Route::get('/google/auto-refresh', 'AuthController@googleRefreshToken');
Route::get('/youtube/get-channels-list/{uid}', 'AuthController@youtubeGetChannels');
Route::get('/youtube/insert-video/{uid}', 'AuthController@youtubeInsertVideo');
