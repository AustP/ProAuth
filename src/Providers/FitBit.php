<?php

namespace ProAuth\Providers;

class FitBit extends \ProAuth\OAuth2
{
    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = 'https://www.fitbit.com/oauth2/authorize';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://api.fitbit.com/oauth2/token';

    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://api.fitbit.com/1/';

    /**
     * Gets the access and refresh tokens from the token endpoint
     */
    public function grant(...$args)
    {
        $base64 = base64_encode($this->id . ':' . $this->secret);
        $this->tokenHeaders = ['Authorization' => 'Basic ' . $base64];

        return parent::grant(...$args);
    }
}
