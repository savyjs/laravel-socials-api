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
Route::get('/google/auth-classic/{provider}', 'Google\AuthController@googleAuthClassic');
Route::get('/google/auth/{provider}?secret={secret}', 'Google\AuthController@googleAuth')->name('googleAuth');
Route::get('/google/back/{provider}', 'Google\AuthController@googleAuthBack');
