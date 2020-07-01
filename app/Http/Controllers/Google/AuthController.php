<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Google\GoogleAuthTrait;
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
    use GoogleAuthTrait;


    public function googleAuthClassic($providerName, GoogleAuthRequest $request)
    {
        $uid = $request->uid; // google user id
        $user_id = intval($request->user_id); // platform user id
        if (!$user_id) die('پلتفرم نا مشخص.');
        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            die('شبکه ی اجتماعی هدف نا مشخص است.');
        }

        // check if uid+provide exists in db - if not create it first
        $user = [
            'uid' => $uid,
            'user_id' => $user_id,
            'provider' => $providerName,
        ];
        session()->put('uid', $uid);
        session()->put('user_id', $user_id);


        $this->setGoogleScopeAndRedirect($providerName);

        // Request authorization from the user.
        $authUrl = $this->client->createAuthUrl();
        return response()->redirectTo($authUrl);

    }

    public function googleAuth($providerName, GoogleAuthRequest $request)
    {
        $secret = ($request->secret);
        $this->row = $row = Token::where(['secret' => $secret])->first();

        if (!$this->row || !$this->row->uid || !$this->row->user_id) {
            return die('کاربر یا پلتفرم موجود نیست.');
        }
        $user = [
            'uid' => $this->row->uid,
            'user_id' => $this->row->user_id,
            'provider' => $providerName,
        ];

        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            die('شبکه ی اجتماعی هدف نا مشخص است.');
        }

        // check if uid+provide exists in db - if not create it first

        session()->put('secret', $secret);
        session()->put('uid', $this->row->uid);
        session()->put('user_id', $this->row->user_id);

        $this->setGoogleScopeAndRedirect($providerName);
        $this->client->setPrompt('consent');
        // Request authorization from the user.
        $authUrl = $this->client->createAuthUrl();
        return response()->redirectTo($authUrl);
    }

    public function googleAuthBack($providerName, GoogleAuthRequest $request)
    {
        $secret = session()->get('secret');
        $uid = session()->get('uid');
        $user_id = session()->get('user_id');
        if (!$uid || !$user_id) {
            return $this->error('مدت زمان بازگشت خیلی طولانی شد. لطفا مجددا وارد شوید.');
        }
        if (!$uid) {
            return $this->error('ورود منقضی شده است. لطفا پنجره را ببندید دوباره باز کنید.');
        }
        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            return $this->error('مشکلی در لاگین رخ داده است!');
        }
        // check if uid+provide exists in db - if not create it first
        $user = [
            'secret' => $secret,
            'uid' => $uid,
            'user_id' => $user_id,
            'provider' => $providerName,
        ];
        dd($user);
        $row = Token::where($user)->first();
        $authCode = trim($request->code);
        $this->setGoogleScopeAndRedirect($providerName);

        // Exchange authorization code for an access token.
        try {
            // exchange code for access_token and refresh token
            $exchanged = $this->client->fetchAccessTokenWithAuthCode($authCode);
            //dd($exchanged);
            $access_token = $exchanged['access_token'];
            $refresh_token = $exchanged['refresh_token'] ?? null;
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
            $userInfo->user_id = $uid;
            $userInfo->auth_row_id = $row->id;
            return die('<script>parent.close();</script>');
        } catch (\Exception $e) {
            return response($e->getMessage(),$e->getTraceAsString());
        }

    }

    function getGoogleAccessToken($providerName, GoogleAuthRequest $request)
    {
        $user_id = auth()->user()->id;
        $uid = $request->uid;
        $this->setGoogleScopeAndRedirect($providerName);

        if ($this->checkGoogleAccess($providerName, $uid, $user_id, false)) {
            try {
                if ($this->row->user_id == $user_id) {
                    return response()->json(['status' => true, 'access_token' => $this->row->access_token]);
                } else {
                    return response()->json(['status' => false, 'data' => 'noPresetValid', 'error' => 'login to youtube first']);
                }
            } catch (\Exception $e) {
                // TODO: show Error
                return response($e->getMessage());
            }
        } else {
            return response()->json(['status' => false, 'data' => 'noToken', 'error' => 'login to youtube first']);
        }
    }

    function getGoogleProfile($providerName, GoogleAuthRequest $request)
    {
        $user_id = auth()->user()->id;
        $uid = $request->uid;
        $this->setGoogleScopeAndRedirect($providerName);

        if ($this->checkGoogleAccess($providerName, $uid, $user_id)) {
            try {
                if ($this->row->user_id == $user_id) {
                    return response()->json($this->userInfo);
                } else {
                    return $this->error('مشکلی در تایید هویت رخ داد . ');
                }
            } catch (\Exception $e) {
                // TODO: show Error
                return response($e->getMessage());
            }
        }
    }

    function revokeGoogleAccess($providerName, GoogleAuthRequest $request)
    {
        $user_id = auth()->user()->id;
        $uid = intval($request->uid);
        $revoke = $this->revokeGoogleAccessToken($providerName, $uid, $user_id, true);
        if ($revoke) {
            return response()->json([
                'status' => true,
                'data' => 'با موفقیت حذف شد . '
            ]);
        } else {
            return response()->json([
                'status' => false,
                'data' => 'مشکلی در حذف توکن رخ داد'
            ]);
        }
    }

    function getGoogleProfileOrAuthLink($providerName, GoogleAuthRequest $request)
    {
        $user_id = auth()->user()->id;
        $uid = $request->uid;
        $this->setGoogleScopeAndRedirect($providerName);

        if ($this->checkGoogleAccess($providerName, $uid, $user_id, false)) {

            try {
                if ($this->row->user_id == $user_id) {
                    return response()->json([
                        'status' => true,
                        'type' => 'profile',
                        'data' => $this->userInfo,
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'type' => 'error',
                        'data' => 'wrong_user_id',
                    ]);
                }
            } catch (\Exception $e) {
                // TODO: show Error
                return response()->json([
                    'status' => false,
                    'type' => 'error',
                    'data' => $e->getMessage(),
                ]);
            }
        } else {
            $link = $this->googleAuthLink($uid, $user_id, $providerName);
            return response()->json([
                'status' => true,
                'type' => 'link',
                'data' => $link,
            ]);
        }
    }

    // refresh all tokens
    public function googleRefreshAllTokens()
    {

        $uids = Token::whereNotNull('refresh_token')->where('end_time', ' > ', time())->get();

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
                return response($e->getMessage());
            }
        }
    }


}
