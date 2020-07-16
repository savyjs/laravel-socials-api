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
    public function getUnfollwersList()
    {
        $list = Twitter::getFriendshipsLookup(['screen_name' => 'savyedinson', 'connections' => 'followed_by', 'count' => "2500", 'format' => 'array']);
    }

    public function getUnfollowers()
    {
        ini_set('max_execution_time', '5000');
        $unfollowed = [];
        $hasUser = true;
        $cursor = file_get_contents('cursor.txt');
        $allFollowers = [];
        echo '-b1-';
        while ($hasUser) {
            echo '-while cycle-';
            sleep(rand(1, 3));
            echo $cursor . "<br />";
            try {
                if ($cursor) {
                    $following = TweetController::getFollowing(['screen_name' => 'ehsant_',
                        'cursor' => $cursor, 'count' => "100", 'format' => 'array']);
                    foreach ($following['users'] as $i => $user) {
                        if (!$user['followed_by']) {
                            Twitter::postUnfollow(['user_id' => $user['id']]);
                            array_push($unfollowed, $user['screen_name']);
                            echo '-b6: - <br />' . $user['screen_name'] . '<br />';
                        }
                    }
                    if (count($following['users']) && $following['next_cursor']) {
                        $cursor = $following['next_cursor_str'];
                        $select = $cursor;
                        file_put_contents('cursor.txt', $select);
                    } else {
                        $hasUser = false;
                        break;
                    }
                } else {
                    echo '-b10-';
                    $hasUser = false;
                    break;
                }
            } catch (\Exception $e) {
                echo '<hr />' . 'failed server:' . $e->getMessage() . '<br />';
                echo 'now:' . time() . ' <br /> will refresh again at: 180 seconds ' . '<meta http-equiv="refresh" content="180">';
                $hasUser = false;
                echo '-b9-';
                break;
            }
        }
        echo '-b8-';
        file_put_contents('cursor.txt', "-1");

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
                    $followers = TweetController::getFollowing(['screen_name' => 'savyedinson', 'cursor' => $cursor, 'count' => 100, 'format' => 'array']);
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
                    $followers = TweetController::getFollowers(['screen_name' => 'savyedinson', 'cursor' => $cursor, 'count' => 100, 'format' => 'array']);
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