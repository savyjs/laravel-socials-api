<?php
/**
 * Created by PhpStorm.
 * User: savvy
 * Date: 19/05/2020
 * Time: 12:11 AM
 */

namespace App;


class GoogleHelper
{

    //
    public function __construct()
    {
        $this->user = [];
        if (auth()->user()) {
            $this->user = auth()->user();
        }
        $this->API_KEY = \Config::get('provider.GOOGLE_API_KEY');

        // get client_id + API_KEY + client_secret

        $client = new Google_Client();
        $client->setAuthConfig('../google.json');
        $client->setAccessType('offline');
        $client->setDeveloperKey($this->API_KEY);
        $this->client = $client;

    }

    function setGoogleScopeAndRedirect($providerName)
    {
        $scopes = \Config::get('provider.' . $providerName);
        $this->client->setScopes($scopes);
        $this->client->setRedirectUri(\Config::get('app.url') . '/google/back/' . $providerName);
    }

}