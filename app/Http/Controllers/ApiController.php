<?php

namespace App\Http\Controllers;

use App\Http\Requests\BloggerApiRequest;
use App\Http\Requests\GoogleApiRequest;
use App\Http\Requests\YoutubeApiRequest;
use http\Env\Response;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function googleApi(GoogleApiRequest $request)
    {
        return General::RequestToGoogle($request->method, $request->uri, $request->params);
    }

    public function youtubeApi(YoutubeApiRequest $request)
    {
        return General::RequestToGoogle($request->method, '/youtube/v3' . $request->uri, $request->data);
    }

    public function bloggerApi(BloggerApiRequest $request)
    {
        return General::RequestToGoogle($request->method, '/blogger/v3' . $request->uri, $request->params);
    }
}
