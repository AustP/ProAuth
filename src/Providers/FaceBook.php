<?php

namespace ProAuth\Providers;

class FaceBook extends \ProAuth\OAuth2
{
    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = 'https://www.facebook.com/dialog/oauth';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://graph.facebook.com/v2.5/oauth/access_token';

    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://graph.facebook.com/';
}
