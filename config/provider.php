<?php

return [
    // google API keys
    'GOOGLE_API_KEY' => env('GOOGLE_API_KEY'),
    'GOOGLE_CLIENT_ID' => env('GOOGLE_CLIENT_ID'),
    'GOOGLE_CLIENT_SECRET' => env('GOOGLE_CLIENT_SECRET'),

    // scopes
    "youtube" => [
        'openid',
        'profile',
        'email',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.channel-memberships.creator',
        'https://www.googleapis.com/auth/youtube.force-ssl',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtubepartner',
        'https://www.googleapis.com/auth/youtubepartner-channel-audit',
    ],
    "blogger" => [
        'openid',
        'profile',
        'email',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/blogger'
    ],
    "googleplus" => [
        'openid',
        'profile',
        'email',
        'https://www.googleapis.com/auth/plus.media.upload',//
        'https://www.googleapis.com/auth/plus.stream.write',//
        'https://www.googleapis.com/auth/plus.circles.write',//
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/contacts'
    ],

];