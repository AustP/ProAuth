<?php

namespace ProAuth\Providers;

class Jawbone extends \ProAuth\OAuth2
{
    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = 'https://jawbone.com/auth/oauth2/auth';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://jawbone.com/auth/oauth2/token';

    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://jawbone.com/nudge/api/v.1.1/';
}
