<?php

namespace App\Http\Controllers\Twitter;

ini_set('memory_limit', '3G');

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleAuthRequest;
use App\Token;
use Composer\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Thujohn\Twitter\Facades\Twitter;

class TwitterController extends Controller
{
    use TwitterTrait;
    use UnfollowerTrait;

    public $userInfo = null;

    /**
     * @param GoogleAuthRequest $request - [
     * uid
     * attachment
     * text
     *
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function favTweet(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;
            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
                if (is_object($credentials)) {
                    $id = $request->id;
                    $response = $this->favTweetByID($id);
                    return response()->json($response);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getUserLastTweet(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;
            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
                if (is_object($credentials)) {
                    $userId = $request->userId;
                    $response = $this->getUserLastTweetByID($userId);
                    return response()->json($response);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
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
    public function unfollowUser(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;
            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
                if (is_object($credentials)) {
                    $userId = $request->userId;
                    $response = $this->twitterUnfollowUser($userId);
                    return response()->json($response);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function unfollowMuteUser(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;
            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
                if (is_object($credentials)) {
                    $userId = $request->userId;
                    $response[] = $this->twitterUnfollowUser($userId);
                    $response[] = $this->twitterMuteUser($userId);
                    return response()->json($response);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function unfollowBlockUser(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;
            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
                if (is_object($credentials)) {
                    $userId = $request->userId;
                    $response = $this->twitterBlockUser($userId);
                    return response()->json($response);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getTwitterUnfollowers(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;
            $cursor = 0;
            if (isset($request->cursor)) $cursor = $request->cursor ?? 0;
            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
                if (is_object($credentials)) {
                    $users = $this->getUnfollowedUsers($pid, $authId, $cursor);
                    if (!count($users)) dd($users, $pid, $authId, $cursor);
                    return response()->json($users);
                } else {
                    return response()->json([
                        'status' => 401,
                        'text' => 'Login to twitter first'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 401,
                    'text' => 'Login to twitter first!'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error2' => $e->getMessage()]);
        }
    }

    function getUnfollowedUsers($pid, $authId, $cursor = 0)
    {
        if ($this->checkTwitterAccess($pid, $authId, true)) {
            $name = ($this->userInfo)->screen_name;
            return $this->getUnfollowers($name, $cursor);
        } else {
            return ['error' => 'access denied!'];
        }
    }

    public function getTwitterFollowers(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;
            $cursor = 0;
            if (isset($request->cursor)) $cursor = $request->cursor ?? 0;
            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
                if (is_object($credentials)) {
                    $users = $this->getFollowerUsers($pid, $authId, $cursor);
                    if (!count($users)) dd($users, $pid, $authId, $cursor);
                    return response()->json($users);
                } else {
                    return response()->json([
                        'status' => 401,
                        'text' => 'Login to twitter first'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 401,
                    'text' => 'Login to twitter first!'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error2' => $e->getMessage()]);
        }
    }

    function getFollowerUsers($pid, $authId, $cursor = 0)
    {
        if ($this->checkTwitterAccess($pid, $authId, true)) {
            $name = ($this->userInfo)->screen_name;
            return $this->followers($name, $cursor);
        } else {
            return ['error' => 'access denied!'];
        }
    }

    public function getTwitterFollowings(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;
            $cursor = 0;
            if (isset($request->cursor)) $cursor = $request->cursor ?? 0;
            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
                if (is_object($credentials)) {
                    $users = $this->getFollowingUsers($pid, $authId, $cursor);
                    if (!count($users)) dd($users, $pid, $authId, $cursor);
                    return response()->json($users);
                } else {
                    return response()->json([
                        'status' => 401,
                        'text' => 'Login to twitter first'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 401,
                    'text' => 'Login to twitter first!'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error2' => $e->getMessage()]);
        }
    }

    function getFollowingUsers($pid, $authId, $cursor = 0)
    {
        if ($this->checkTwitterAccess($pid, $authId, true)) {
            $name = ($this->userInfo)->screen_name;
            return $this->followings($name, $cursor);
        } else {
            return ['error' => 'access denied!'];
        }
    }

    //
    function getTwitterProfileOrAuthLink(GoogleAuthRequest $request)
    {
        $providerName = 'twitter';
        $authId = auth()->user()->id;
        $pid = $request->uid;

        if ($this->checkTwitterAccess($pid, $authId, false)) {

            try {
                if ($this->row->user_id == $authId) {
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
            $link = $this->twitterAuthLink($pid, $authId);
            return response()->json([
                'status' => true,
                'type' => 'link',
                'data' => $link,
            ]);
        }
    }

    function getTwitterProfileById(GoogleAuthRequest $request)
    {
        $providerName = 'twitter';
        $authId = auth()->user()->id;
        $pid = $request->uid;
        $userId = $request->id;

        if ($this->checkTwitterAccess($pid, $authId, false)) {

            try {
                if ($this->row->user_id == $authId) {
                    $userInfo = \Cache::remember($userId, 60, function () use ($userId) {
                        return $this->getUserById(['user_id' => $userId]);
                    });
                    return response()->json([
                        'status' => true,
                        'type' => 'profile',
                        'data' => $userInfo,
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
            return response()->json([
                'status' => false,
                'type' => 'auth error',
                'data' => 'login first',
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
        $pid = session()->get('uid');
        $authId = session()->get('user_id');
        if (!$pid || !$authId) {
            return $this->error('مدت زمان بازگشت خیلی طولانی شد. لطفا مجددا وارد شوید.');
        }
        if (!$pid) {
            return $this->error('ورود منقضی شده است. لطفا پنجره را ببندید دوباره باز کنید.');
        }
        $providers = Token::$PROVIDERS;
        if (!in_array($providerName, $providers)) {
            return $this->error('مشکلی در لاگین رخ داده است!');
        }
        // check if uid+provide exists in db - if not create it first
        $user = [
            'secret' => $secret,
            'uid' => $pid,
            'user_id' => $authId,
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


    public
    function sendTweet(GoogleAuthRequest $request)
    {
        try {
            $providerName = 'twitter';
            $authId = auth()->user()->id;
            $pid = $request->uid;

            if ($this->checkTwitterAccess($pid, $authId, true)) {
                $credentials = $this->userInfo;
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
