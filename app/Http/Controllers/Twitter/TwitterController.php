<?php

namespace App\Http\Controllers\Twitter;

ini_set('memory_limit', '3G');

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleAuthRequest;
use App\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Thujohn\Twitter\Facades\Twitter;

class TwitterController extends Controller
{
    use TwitterTrait;
    use UnfollowerTrait;

    //
    function getTwitterProfileOrAuthLink(GoogleAuthRequest $request)
    {
        $providerName = 'twitter';
        $user_id = auth()->user()->id;
        $uid = $request->uid;

        if ($this->checkTwitterAccess($uid, $user_id, false)) {

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
            $link = $this->twitterAuthLink($uid, $user_id);
            return response()->json([
                'status' => true,
                'type' => 'link',
                'data' => $link,
            ]);
        }
    }

    public function twitterAuth(GoogleAuthRequest $request)
    {
        $providerName = 'twitter';
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

        // your SIGN IN WITH TWITTER  button should point to this route
        $sign_in_twitter = true;
        $force_login = false;

        // Make sure we make this request w/o tokens, overwrite the default values in case of login.
        Twitter::reconfig(['token' => '', 'secret' => '']);
        $backLink = route('twitter.callback');
        $token = Twitter::getRequestToken($backLink);

        if (isset($token['oauth_token_secret'])) {
            $url = Twitter::getAuthorizeURL($token, $sign_in_twitter, $force_login);

            Session::put('oauth_state', 'start');
            Session::put('oauth_request_token', $token['oauth_token']);
            Session::put('oauth_request_token_secret', $token['oauth_token_secret']);

            return Redirect::to($url);
        }
        die('برای لاگین با تویتر مشکلی رخ داد. به مدیریت خبر بدهید.');

    }

    public function twitterAuthBack()
    {
        $providerName = 'twitter';
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
        //dd($user);
        $row = Token::where($user)->first();

        if (Session::has('oauth_request_token')) {
            $request_token = [
                'token' => Session::get('oauth_request_token'),
                'secret' => Session::get('oauth_request_token_secret'),
            ];

            Twitter::reconfig($request_token);

            $oauth_verifier = false;

            if (\Illuminate\Support\Facades\Request::has('oauth_verifier')) {
                $oauth_verifier = \Illuminate\Support\Facades\Request::get('oauth_verifier');
                // getAccessToken() will reset the token for you
                $token = Twitter::getAccessToken($oauth_verifier);
            }

            if (!isset($token['oauth_token_secret'])) {
                return Redirect::route('twitter.error')->with('flash_error', 'We could not log you in on Twitter.');
            }
            $credentials = Twitter::getCredentials();


            if (is_object($credentials) && !isset($credentials->error)) {
                // $credentials contains the Twitter user object with all the info about the user.
                // Add here your own user logic, store profiles, create new users on your tables...you name it!
                // Typically you'll want to store at least, user id, name and access tokens
                // if you want to be able to call the API on behalf of your users.

                // This is also the moment to log in your users if you're using Laravel's Auth class
                // Auth::login($user) should do the trick.

                $row->access_token = json_encode([
                    'token' => $token['oauth_token'],
                    'secret' => $token['oauth_token_secret'],
                ]);
                $row->save();
                //dd($row, $credentials, $token, $request_token);
                return die('<script>parent.close();</script>');
            }

            return Redirect::route('twitter.error')->with('flash_error', 'Crab! Something went wrong while signing you up!');
        }
    }

    /**
     * @param GoogleAuthRequest $request - [
     * uid
     * attachment
     * text
     *
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfollowing(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $user_id = auth()->user()->id;
            $uid = $request->uid;

            if ($this->checkTwitterAccess($uid, $user_id, true)) {
                $credentials = Twitter::getCredentials();
                if (is_object($credentials)) {
                    $users = $this->getUnfollowedUsers($uid, $user_id);
                    return response()->json(['sent' => $users]);
                } else {
                    return response()->json([
                        'status' => 401,
                        'text' => 'Login to twitter first'
                    ]);
                }
            }
        } catch (\Exception $e) {
            response()->json(['error2' => $e->getMessage()]);
        }

    }

    function getUnfollowedUsers($uid, $user_id)
    {
        if ($this->checkTwitterAccess($uid, $user_id, true)) {

        }
    }


    public
    function sendTweet(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $user_id = auth()->user()->id;
            $uid = $request->uid;

            if ($this->checkTwitterAccess($uid, $user_id, true)) {
                $credentials = Twitter::getCredentials();
                if (is_object($credentials)) {
                    $attachment = isset($request->attachment) ? $request->attachment : null;
                    $text = $request->text;
                    $status = ['status' => $text];
                    if ($attachment) {
                        $filename = basename($attachment);
                        $tempFile = tempnam(sys_get_temp_dir(), $filename);
                        try {
                            copy($attachment, $tempFile);
                        } catch (\Exception $ce) {
                            return response()->json(['status' => 401, 'error-1', 'text' => $ce->getMessage()]);
                        }

                        if (!file_exists($tempFile)) {
                            return response()->json(['status' => 401, 'error-1', 'text' => 'file dosn"t exists']);
                        }
                        if (file_exists($tempFile)) {
                            try {
                                $uploaded_media = Twitter::uploadMedia(['media' => File::get($tempFile)]);
                            } catch (\Exception $f) {
                                dd($f);
                                return response()->json(['status' => 401, 'error0', 'text' => $f->getMessage(), $f]);
                            }
                            $status['media_ids'] = $uploaded_media->media_id_string;
                        } else {
                            return response()->json(['status' => 401, 'error1', 'text' => $tempFile . ' dosn`t exists']);
                        }
                    }
                    $sent = Twitter::postTweet($status);
                    try {
                        if ($tempFile) {
                            unlink($tempFile);
                            File::delete($tempFile);
                        }
                    } catch (\Exception $d) {
                        //return response()->json(['status' => 401, 'error00', 'text' => $d->getMessage()]);
                    }
                    return response()->json(['sent' => $sent]);
                } else {
                    return response()->json([
                        'status' => 401,
                        'text' => 'Login to twitter first'
                    ]);
                }
            }
        } catch (\Exception $e) {
            response()->json(['error2' => $e->getMessage()]);
        }

    }
}
