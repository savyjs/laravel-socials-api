<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneralRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class General extends Controller
{
    public static function ApiCall($method, $url, $params)
    {
        //dd($method,$url,$params);
        $client = new \GuzzleHttp\Client([
            \GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath()
        ]);
        try {
            $serviceResponse = $client->request($method, $url, $params);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
        //$response = $serviceResponse->getBody()->getContents();
        $response = $serviceResponse;
        return $response;
    }

    public static function RequestToGoogle($method, $uri, $data = [], $API_KEY = null, $access_token = null)
    {
        $defaultGoogleAPIPath = 'https://www.googleapis.com';
        $ApiPath = '';
        $url = $defaultGoogleAPIPath . $ApiPath . $uri;
        $params = [];
        if ($data) $params['body'] = $data;
        if ($API_KEY) $params['query']['API_KEY'] = $API_KEY;
        if ($access_token) $params['header']['Authorization'] = 'Bearer ' + $API_KEY;
        return $response = self::ApiCall($method, $url, $data, $params);
    }

    public function clearDB()
    {
        // dd(Carbon::now()->toDateTimeString());
        $list = DB::table('oauth_access_tokens')
            // ->whereNotNull('expires_at')
            ->where('expires_at', '<>', Carbon::now()->toDateTimeString())
            ->count();
        return dd(1,$list);
    }

}
