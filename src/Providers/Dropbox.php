<?php

namespace ProAuth\Providers;

class Dropbox extends \ProAuth\OAuth2
{
    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://content.dropboxapi.com/2';

    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = 'https://www.dropbox.com/oauth2/authorize';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://www.dropbox.com/oauth2/token';
}
