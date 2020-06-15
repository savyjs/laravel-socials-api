<?php
/**
 * Created by PhpStorm.
 * User: savvy
 * Date: 19/05/2020
 * Time: 09:47 AM
 */

namespace App\Http\Controllers\Google;

use App\Token;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Oauth2;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_ThumbnailDetails;
use Google_Service_YouTube_Thumbnail;
use Google_Service_YouTube_VideoSnippet;
use Illuminate\Support\Facades\Hash;
use function Safe\ssdeep_fuzzy_hash;


trait GoogleAuthTrait
{
    public function __construct()
    {
        $this->user = [];
        if (auth()->user()) {
            $this->user = auth()->user();
        }
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

    public function googleAuthLink($uid, $user_id, $providerName)
    {
        $user_id = intval($user_id); // platform user id
        if (!$user_id) die('پلتفرم نا مشخص.');
        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            die('شبکه ی اجتماعی هدف نا مشخص است.');
        }

        // check if uid+provide exists in db - if not create it first

        $secret = (\Str::random() . (time() . $user_id . $uid));
        $user = [
            'uid' => $uid,
            'user_id' => $user_id,
            'secret' => $secret,
            'provider' => $providerName,
        ];
        Token::firstOrCreate($user);
        $link = route('googleAuth', [$providerName]) . '?secret=' . $secret;

        // Request authorization from the user.
        return $link;

    }


    function refreshOneRow($uid, $providerName, $error = true)
    {

        $row = Token::whereNotNull('refresh_token')->whereUidAndProvider($uid, $providerName)->first();
        if (!$uid || !$row || $row->uid != $uid) {
            if ($error) return $this->error('توکنی موجود نیست.');
        }

        if (!$row->access_token) {
            $row->delete();
            if ($error) return $this->error('توکن خالی است.');
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
            return true;
        } catch (\Exception $e) {
            // set log that can't to refresh token
            if ($error) return response($e->getMessage());
            return false;
        }

    }

    function getGoogleAccessToken($providerName, $uid, $user_id = null, $error = true)
    {
        return $this->checkGoogleAccess($providerName, $uid, $user_id = null, $error = true);
    }

    function checkGoogleAccess($providerName, $uid, $user_id = null, $error = true)
    {
        $user = [
            'uid' => $uid,
            'provider' => $providerName,
        ];
        if ($user_id) {
            $user['user_id'] = $user_id;
        }

        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            if ($error) $this->error('مشکلی در پیدا رخ داد');
            return false;
        }

        $row = Token::where($user)->first();
        // dd($row,$user);

        // dd($row->access_token);
        if (!$uid || !$row || $row->uid != $uid) {
            if ($error) $this->error('توکنی موجود نیست');
            return false;
        }
        if (!$row->access_token) {
            if ($error) $this->error('توکن خالی است');
            $row->delete();
            return false;
        }
        if ($row->end_time < time()) {
            $this->refreshOneRow($row->uid, $row->provider, $error);
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
            $this->userInfo = $userInfo;
            return true;
        } catch (\Exception $e) {
            // TODO: add error handler
            $row->delete();
            if ($error) return response($e->getMessage());
            return false;
        }
    }


    function revokeGoogleAccessToken($providerName, $uid, $user_id, $error = true)
    {
        if (!$user_id) {
            if ($error) return $this->error('شناسه پلتفرم الزامی است');
            return false;
        }
        $user = [
            'uid' => $uid,
            'user_id' => $user_id,
            'provider' => $providerName,
        ];

        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            if ($error) $this->error('مشکلی در پیدا رخ داد');
            return false;
        }

        $this->row = Token::where($user)->first();

        if (!$uid || !$this->row || $this->row->uid != $uid) {
            if ($error) $this->error('توکنی موجود نیست');
            return false;
        }
        if (!$this->row->access_token) {
            $this->row->delete();
            return true;
        }

        $this->setGoogleScopeAndRedirect($providerName);

        try {
            $this->client->revokeToken($this->row->access_token);
            $this->row->delete();
            return true;
        } catch (\Exception $e) {
            // TODO: add error handler
            $this->row->delete();
            if ($error) return response($e->getMessage());
            return true;
        }
    }


    public function error($msg, $status = 400)
    {
        throw new \Exception($msg ?? 'مشکلی رخ داد');
    }


}