<?php
/**
 * Created by PhpStorm.
 * User: savvy
 * Date: 16/07/2020
 * Time: 07:25 PM
 */

namespace App\Http\Controllers\Twitter;


use Thujohn\Twitter\Facades\Twitter;

trait UnfollowerTrait
{
    public function getUnfollwersList($screen_name)
    {
        $list = Twitter::getFriendshipsLookup(['screen_name' => $screen_name, 'connections' => 'followed_by', 'count' => "100", 'format' => 'array']);
        $unfollowed = [];
        foreach ($list as $item) {
            if (isset($item['connections']) && isset($item['connections'][0]) && ($item['connections'][0] == 'following') && (!isset($item['connections'][1]) || ($item['connections'][1] != 'followed_by'))) {
                $unfollowed[] = $item;
            }
        }
        return $unfollowed;
    }

    public function unfollowUser($user)
    {
        Twitter::postUnfollow(['user_id' => $user['id']]);
    }

    public function getUnfollowers($name, $cursor = 0)
    {
        try {
            ini_set('max_execution_time', '5000');
            $hasUser = true;
            $unfollowed = [];
            try {
                if ($cursor) {
                    $following = $this->getFollowing(['cursor' => $cursor, 'screen_name' => $name, 'count' => "100", 'format' => 'array']);
                } else {
                    $following = $this->getFollowing(['screen_name' => $name, 'count' => "100", 'format' => 'array']);
                }
                $screen_names = collect($following['users'])->pluck(['screen_name'])->implode(',');
                $connections = $this->getUnfollwersList($screen_names);
                $unfollowed_usernames = collect($connections)->pluck('screen_name')->toArray();
                $unfollowed_users = collect($following['users'])->whereInStrict('screen_name', $unfollowed_usernames)->all();
                array_merge($unfollowed, $unfollowed_users);
                if (isset($following['next_cursor'])) {
                    $cursor = $following['next_cursor_str'];
                    $select = $cursor;
                } else {
                    $select = null;
                    $hasUser = false;
                }
                return ['phase' => 1, 'count' => count($following['users']), 'name' => $name, 'cursor' => $select, 'list' => $unfollowed_users, 'hasUsers' => $hasUser];
            } catch (\Exception $e) {
                //echo '<hr />' . 'failed server:' . $e->getMessage() . '<br />';
                $hasUser = false;
                return ['phase' => 6, 'name' => $name, 'cursor' => -2, 'wait_time' => 180, 'list' => [], 'error' => 'failed server:' . $e->getMessage(), 'hasUsers' => false];
            }
        } catch (\Exception $e) {
            return ['phase' => 8, 'name' => $name, 'cursor' => -2, 'list' => [], 'error' => 'failed server:' . $e->getMessage(), 'hasUsers' => false];
            dd($e);
        }
    }

    public static function getFollowers($arg)
    {
        try {
            return Twitter::getFollowers($arg);
        } catch (Exception $e) {
            // dd(Twitter::error());
            return dd(Twitter::logs());
        }

    }

    public static function getFollowing($arg)
    {
        try {
            return Twitter::getFriends($arg);
        } catch (Exception $e) {
            // dd(Twitter::error());
            return dd(Twitter::logs());
        }

    }

    public function followings()
    {
        $hasUser = true;
        $cursor = "-1";
        $allFollowers = [];
        while ($hasUser) {
            sleep(rand(1, 2));
            echo $cursor . "<br />";
            try {
                $user = \Session::get('user_object');
                if ($cursor) {
                    $followers = $this->getFollowing(['screen_name' => 'savyedinson', 'cursor' => $cursor, 'count' => 100, 'format' => 'array']);
                } else {
                    $hasUser = false;
                    break;
                }
                if (count($followers['users']) && $followers['next_cursor']) {
                    $cursor = $followers['next_cursor_str'];
                    array_push($allFollowers, $followers['users']);
                } else {
                    $hasUser = false;
                    break;
                }
            } catch (\Exception $e) {
                print_r($e);
                $hasUser = false;
                break;
            }
        }
    }

    public function followers()
    {
        $hasUser = true;
        $cursor = "-1";
        $allFollowers = [];
        while ($hasUser) {
            sleep(rand(1, 2));
            echo $cursor . "<br />";
            try {
                $user = \Session::get('user_object');
                if ($cursor) {
                    $followers = $this->getFollowers(['screen_name' => 'savyedinson', 'cursor' => $cursor, 'count' => 100, 'format' => 'array']);
                } else {
                    $hasUser = false;
                    break;
                }
                if (count($followers['users']) && $followers['next_cursor']) {
                    $cursor = $followers['next_cursor_str'];
                    array_push($allFollowers, $followers['users']);
                } else {
                    $hasUser = false;
                    break;
                }
            } catch (\Exception $e) {
                print_r($e);
                $hasUser = false;
                break;
            }
        }
        \Session_set('followers', $allFollowers);
    }
}
