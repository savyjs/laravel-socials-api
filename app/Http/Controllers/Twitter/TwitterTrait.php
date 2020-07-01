<?php
/**
 * Created by PhpStorm.
 * User: savvy
 * Date: 01/07/2020
 * Time: 08:35 AM
 */

namespace App\Http\Controllers\Twitter;


use App\Token;
use Thujohn\Twitter\Facades\Twitter;

trait TwitterTrait
{
    public function __construct()
    {
        $this->user = [];
        if (auth()->user()) {
            $this->user = auth()->user();
        }
        $this->row = null;
    }

    /*
     * uid => user id in the media platform -> preset_id
     * $user_id => user id in our sqlite database
     */
    public function twitterAuthLink($uid, $user_id)
    {
        $providerName = 'twitter';
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
        $link = route('twitterAuth') . '?secret=' . $secret;

        // Request authorization from the user.
        return $link;

    }

    function checkTwitterAccess($uid, $user_id = null, $error = true)
    {
        $providerName = 'twitter';
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

        try {
            $request_token = collect(json_decode($row->access_token))->toArray();
            // dd($request_token);
            Twitter::reconfig($request_token);
            $userInfo = Twitter::getCredentials();
            //dd($userInfo);
            $userInfo->uid = $uid;
            $userInfo->user_id = $user_id;
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


    public function error($msg, $status = 400)
    {
        throw new \Exception($msg ?? 'مشکلی رخ داد');
    }

}