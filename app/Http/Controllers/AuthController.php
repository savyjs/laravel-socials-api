<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoogleAuthRequest;
use App\Token;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Oauth2;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_ThumbnailDetails;
use Google_Service_YouTube_Thumbnail;
use Google_Service_YouTube_VideoSnippet;

class AuthController extends Controller
{
    //
    public function __construct()
    {
        $this->API_KEY = \Config::get('provider.GOOGLE_API_KEY');

        // get client_id + API_KEY + client_secret

        $client = new Google_Client();
        $client->setAuthConfig('../google.json');
        $client->setAccessType('offline');
        $client->setDeveloperKey($this->API_KEY);
        $this->client = $client;

    }

    function setGoogleScopeAndRedirect($providerName)
    {
        $scopes = \Config::get('provider.' . $providerName);
        $this->client->setScopes($scopes);
        $this->client->setRedirectUri(\Config::get('app.url') . '/google/back/' . $providerName);
    }

    public function googleAuth($providerName, GoogleAuthRequest $request)
    {
        $uid = $request->uid;
        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            die('مشکلی در لاگین رخ داده است!');
        }


        // check if uid+provide exists in db - if not create it first
        $user = [
            'uid' => $uid,
            'provider' => $providerName,
        ];
        session()->put('uid', $uid);

        $row = Token::firstOrCreate($user);
        $this->setGoogleScopeAndRedirect($providerName);

        // Request authorization from the user.
        $authUrl = $this->client->createAuthUrl();
        return response()->redirectTo($authUrl);

    }

    public function googleAuthBack($providerName, GoogleAuthRequest $request)
    {
        $uid = session()->get('uid');
        if (!$uid) {
            return die('ورود منقضی شده است. لطفا پنجره را ببندید دوباره باز کنید.');
        }
        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            die('مشکلی در لاگین رخ داده است!');
        }
        // check if uid+provide exists in db - if not create it first
        $user = [
            'uid' => $uid,
            'provider' => $providerName,
        ];
        $row = Token::firstOrCreate($user);
        $authCode = trim($request->code);
        $this->setGoogleScopeAndRedirect($providerName);


        // Exchange authorization code for an access token.
        try {
            // exchange code for access_token and refresh token
            $exchanged = $this->client->fetchAccessTokenWithAuthCode($authCode);
            //dd($exchanged);
            $access_token = $exchanged['access_token'];
            $refresh_token = $exchanged['refresh_token'];
            $time = time();
            $expire = $exchanged['expires_in'];


            // store code
            $row->access_token = $access_token;
            $row->refresh_token = $refresh_token;
            $row->end_time = $time + $expire;

            // update access_token + refresh_token in uid+provider row
            $row->save();
            // return object of profile + row id + access_token + token_type
            $oauth2 = new Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();
            $userInfo->refresh = true;
            $userInfo->uid = $uid;
            $userInfo->auth_row_id = $row->id;
            return response()->json($userInfo);
        } catch (\Exception $e) {
            dd($e . 'مشکلی در گرفتن توکن رخ داد');
        }

    }


    public function googleRefreshToken()
    {

        $uids = Token::whereNotNull('refresh_token')->where('end_time', '>', time())->get();

        $providers = Token::$PROVIDERS;
        foreach ($uids as $j => $row) {
            $time = time();

            $providerName = $row->provider;
            $this->setGoogleScopeAndRedirect($providerName);


            // Exchange authorization code for an access token.
            try {
                // exchange code for access_token and refresh token
                $refresh_token = $row->refresh_token;
                $exchanged = $this->client->fetchAccessTokenWithRefreshToken($refresh_token);
                $access_token = $exchanged['access_token'];
                $refresh_token = $exchanged['refresh_token'];
                $expire = $exchanged['expires_in'];


                // store code

                // dd($time, $refresh_token, $exchanged, $access_token);

                // store code
                $row->access_token = $access_token;
                $row->refresh_token = $refresh_token;
                $row->end_time = $time + $expire;

                // update access_token + refresh_token in uid+provider row
                $row->save();

            } catch (\Exception $e) {
                // set log that can't to refresh token
                dd($e);
            }
        }
    }


    function refreshOneRow($uid, $providerName)
    {


        $row = Token::whereNotNull('refresh_token')->whereUidAndProvider($uid, $providerName)->where('end_time', '>', time())->first();
        if (!$uid || !$row || $row->uid !== $uid) {
            return die('توکنی موجود نیست.');
        }
        if (!$row->access_token) {
            $row->delete();
            return die('توکن خالی است.');
        }
        if ($row->end_time < time()) {
            $row->delete();
            return die('زمان توکن تمام شده است.');
        }
        // dd($uids);
        $providers = Token::$PROVIDERS;
        $time = time();

        $this->setGoogleScopeAndRedirect($providerName);

        // Exchange authorization code for an access token.
        try {
            // exchange code for access_token and refresh token
            $refresh_token = $row->refresh_token;
            $exchanged = $this->client->fetchAccessTokenWithRefreshToken($refresh_token);
            $access_token = $exchanged['access_token'];
            $refresh_token = $exchanged['refresh_token'];
            $expire = $exchanged['expires_in'];

            $row->access_token = $access_token;
            $row->refresh_token = $refresh_token;
            $row->end_time = $time + $expire;


            // update access_token + refresh_token in uid+provider row
            $row->save();

        } catch (\Exception $e) {
            // set log that can't to refresh token
            dd($e);
        }

    }

    function checkGoogleAccess($providerName, $uid)
    {
        $user = [
            'uid' => $uid,
            'provider' => $providerName,
        ];

        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            die('مشکلی در پیدا رخ داد');
        }


        $row = Token::where($user)->first();
        // dd($row->access_token);
        if (!$uid || !$row || $row->uid !== $uid) {
            return die('توکنی موجود نیست.');
        }
        if (!$row->access_token) {
            $row->delete();
            return die('توکن خالی است.');
        }
        if ($row->end_time < time()) {
            $row->delete();
            return die('زمان توکن تمام شده است.');
        }

        $this->setGoogleScopeAndRedirect($providerName);

        try {
            $this->client->setAccessToken($row->access_token);

            $oauth2 = new Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();
            $userInfo->refresh = true;
            $userInfo->uid = $uid;
            $userInfo->auth_row_id = $row->id;
            $this->row = $row;
            $this->user = $userInfo;
            $this->refreshOneRow($row->uid, $row->provider);
            return true;
        } catch (\Exception $e) {
            // TODO: add error handler
            dd($e);
            $row->delete();
        }
    }


    function getGoogleAccess($providerName, GoogleAuthRequest $request)
    {
        $uid = $request->uid;
        $this->setGoogleScopeAndRedirect($providerName);

        if ($this->checkGoogleAccess($providerName, $uid)) {
            try {
                //dd($this->row);
                return response()->json($this->row->access_token);
            } catch (\Exception $e) {
                // TODO: show Error
                return dd($e);
            }
        }
    }

    function youtubeGetChannels($uid, GoogleAuthRequest $request)
    {
        $cid = $request->channel_id;
        $user = [
            'uid' => $uid,
            'provider' => 'youtube',
        ];

        $this->row = Token::whereUidAndProvider($uid, 'youtube')->first();
        $this->client->setAccessToken($this->row->access_token);

        if ($this->checkGoogleAccess('youtube', $uid)) {
            try {
                $service = new Google_Service_YouTube($this->client);
                $channels = $service->channels->listChannels('brandingSettings', ['id' => $cid]);
                return response()->json($channels);
            } catch (\Exception $e) {
                // TODO: show Error
                return dd($e);
            }
        }
    }

    function youtubeInsertVideo($uid, GoogleAuthRequest $request)
    {

        $user = [
            'uid' => $uid,
            'provider' => 'youtube',
        ];


        $this->row = Token::whereUidAndProvider($uid, 'youtube')->first();
        $this->client->setAccessToken($this->row->access_token);


        if ($this->checkGoogleAccess('youtube', $uid)) {
            try {
                $service = new Google_Service_YouTube($this->client);
                $video = new Google_Service_YouTube_Video($this->client);
                $thumbnailDetails = new Google_Service_YouTube_ThumbnailDetails();
                $thumbnail = new Google_Service_YouTube_Thumbnail();
                $videoSnippet = new Google_Service_YouTube_VideoSnippet();


                // general field
                $videoUrl = $request->videoPath ?? 'C:\Users\savvy\Pictures\gif\1.mp4';
                $title = $request->title ?? 'عنوان ویدیو';
                $categoryId = $request->categoryId ?? '';
                $channelId = $request->channelId ?? '';
                //$channelTitle = $request->channelTitle ?? '';
                $description = $request->description ?? 'توضیحات ویدیو - ارسال شده توسط ساوی مدیا';
                $now   = new \DateTime();
                $clone = $now;        //this doesnot clone so:
                $clone->modify( '1 day' );
                //dd($clone->format('Y-m-d H:i:s'));
                $publishAt = $request->publishAt ?? $clone->format('Y-m-d\TH:i:sO');
                $thumbnailUrl = $request->thumbnailUrl ?? 'https://static.farakav.com/v3content/assets/img/identity/varzesh3-logo-wt.png';

                //$videoSnippet->setCategoryId($categoryId);
                //$videoSnippet->setChannelId($channelId);
                //$videoSnippet->setChannelTitle($channelTitle);
                $videoSnippet->setDescription($description);
                //$videoSnippet->setPublishedAt($publishAt);

                if ($thumbnailUrl) {
                    $thumbnail->setUrl($thumbnailUrl);
                    $thumbnailDetails->setDefault($thumbnail);
                    $videoSnippet->setThumbnails($thumbnailDetails);
                }
                $videoSnippet->setTitle($title);
                //$video->setSnippet($videoSnippet);

                $data = array(
                    'data' => file_get_contents($videoUrl),
                    'mimeType' => 'video/*',
                    'uploadType' => 'multipart'
                );
                $response = $service->videos->insert(
                    'id',
                    $video,
                    $data
                );
                return response()->json($response);
            } catch (\Exception $e) {
                // TODO: show Error
                return dd($e);
            }
        }
    }


}
