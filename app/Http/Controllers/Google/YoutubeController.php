<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
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

class YoutubeController extends Controller
{
    use GoogleAuthTrait;

    //
    function youtubeGetChannelDetails(GoogleAuthRequest $request)
    {
        $uid = $request->uid;
        $cid = $request->channel_id;
        $user_id = $this->user->user_id;
        $user = [
            'uid' => $uid,
            'user_id' => $uid,
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
                return response($e->getMessage());
            }
        }
    }

    function youtubeGetChannels(GoogleAuthRequest $request)
    {
        $uid = $request->uid;
        $user_id = auth()->user()->id;

        if ($this->checkGoogleAccess('youtube', $uid, $user_id, false)) {
            try {
                $service = new Google_Service_YouTube($this->client);
                $channels = $service->channels->listChannels('brandingSettings', ['mine' => true]);
                return response()->json([
                    'status' => true,
                    'type' => 'list',
                    'data' => $channels
                ]);
            } catch (\Exception $e) {
                // TODO: show Error
                return response()->json([
                    'status' => false,
                    'type' => 'error',
                    'data' => $e->getMessage()
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'type' => 'token',
                'data' => 'مشکلی در گرفتن توکن رخ داد'
            ]);
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
                $now = new \DateTime();
                $clone = $now;        //this doesnot clone so:
                $clone->modify('1 day');
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
                return response($e->getMessage());
            }
        }
    }

}
