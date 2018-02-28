<?php

namespace ProAuth\Providers;

class Google extends \ProAuth\OAuth2
{
    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://www.googleapis.com/oauth2/v1/';

    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = 'https://accounts.google.com/o/oauth2/auth';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://accounts.google.com/o/oauth2/token';
}
