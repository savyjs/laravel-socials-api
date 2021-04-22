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

    public function twitterUnfollowUser($userId)
    {
        try {
            return Twitter::postUnfollow(['user_id' => $userId]);
        } catch (\Exception $e) {
            return ['phase' => 12, 'userId' => $userId, 'wait_time' => 180, 'list' => [], 'error' => 'failed server:' . $e->getMessage()];
        }
    }

    public function twitterMuteUser($userId)
    {
        try {
            return Twitter::muteUser(['user_id' => $userId]);
        } catch (\Exception $e) {
            return ['phase' => 12, 'userId' => $userId, 'wait_time' => 180, 'list' => [], 'error' => 'failed server:' . $e->getMessage()];
        }
    }

    public function twitterBlockUser($userId)
    {
        try {
            return Twitter::postBlock(['user_id' => $userId]);
        } catch (\Exception $e) {
            return ['phase' => 11, 'userId' => $userId, 'wait_time' => 180, 'list' => [], 'error' => 'failed server:' . $e->getMessage()];
        }
    }

    public function getUnfollowers($name, $cursor = 0)
    {
        try {
            ini_set('max_execution_time', '5000');
            $hasUser = true;
            $unfollowed = [];
            try {
                $chacheName = 'followings_of_' . $cursor . '_' . $name;
                //\Cache::forget($chacheName);
                $following = \Cache::remember($chacheName, 48 * 60, function () use ($cursor, $name) {
                    if ($cursor) {
                        return $this->getFollowing(['cursor' => $cursor, 'screen_name' => $name, 'count' => "100", 'format' => 'array']);
                    } else {
                        return $this->getFollowing(['screen_name' => $name, 'count' => "100", 'format' => 'array']);
                    }
                });
                $screen_names = collect($following['users'])->pluck(['screen_name'])->implode(',');
                $followingChacheName = 'unfollowers_of_' . hash('sha256', $screen_names);
                //\Cache::forget($followingChacheName);
                $connections = \Cache::remember($followingChacheName, 60, function () use ($screen_names) {
                    return $this->getUnfollwersList($screen_names);
                });
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
            //dd($e);
        }
    }


    public function followings($name, $cursor = 0)
    {
        try {
            ini_set('max_execution_time', '5000');
            $hasUser = true;
            try {
                $chacheName = 'followings_of_' . $cursor . '_' . $name;
                $following = \Cache::remember($chacheName, 15, function () use ($cursor, $name) {
                    if ($cursor) {
                        return $this->getFollowing(['cursor' => $cursor, 'screen_name' => $name, 'count' => "100", 'format' => 'array']);
                    } else {
                        return $this->getFollowing(['screen_name' => $name, 'count' => "100", 'format' => 'array']);
                    }
                });
                $users = collect($following['users']);
                if (isset($following['next_cursor'])) {
                    $cursor = $following['next_cursor_str'];
                    $select = $cursor;
                } else {
                    $select = null;
                    $hasUser = false;
                }
                return ['phase' => 1, 'count' => count($users), 'name' => $name, 'cursor' => $select, 'list' => $users, 'hasUsers' => $hasUser];
            } catch (\Exception $e) {
                return ['phase' => 6, 'name' => $name, 'cursor' => -2, 'wait_time' => 180, 'list' => [], 'error' => 'failed server:' . $e->getMessage(), 'hasUsers' => false];
            }
        } catch (\Exception $e) {
            return ['phase' => 8, 'name' => $name, 'cursor' => -2, 'list' => [], 'error' => 'failed server:' . $e->getMessage(), 'hasUsers' => false];
        }
    }

    public function followers($name, $cursor = 0)
    {
        try {
            ini_set('max_execution_time', '5000');
            $hasUser = true;
            try {
                $chacheName = 'followers_of_' . $cursor . '_' . $name;
                $following = \Cache::remember($chacheName, 15, function () use ($cursor, $name) {
                    if ($cursor) {
                        return $this->getFollowers(['cursor' => $cursor, 'screen_name' => $name, 'count' => "100", 'format' => 'array']);
                    } else {
                        return $this->getFollowers(['screen_name' => $name, 'count' => "100", 'format' => 'array']);
                    }
                });
                $users = collect($following['users']);
                if (isset($following['next_cursor'])) {
                    $cursor = $following['next_cursor_str'];
                    $select = $cursor;
                } else {
                    $select = null;
                    $hasUser = false;
                }
                return ['phase' => 1, 'count' => count($users), 'name' => $name, 'cursor' => $select, 'list' => $users, 'hasUsers' => $hasUser];
            } catch (\Exception $e) {
                return ['phase' => 6, 'name' => $name, 'cursor' => -2, 'wait_time' => 180, 'list' => [], 'error' => 'failed server:' . $e->getMessage(), 'hasUsers' => false];
            }
        } catch (\Exception $e) {
            return ['phase' => 8, 'name' => $name, 'cursor' => -2, 'list' => [], 'error' => 'failed server:' . $e->getMessage(), 'hasUsers' => false];
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

    public static function getUserById($arg)
    {
        try {
            return Twitter::getUsers($arg);
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
}
