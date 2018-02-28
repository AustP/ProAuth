<?php

namespace ProAuth\Providers;

class FitBit extends \ProAuth\OAuth2
{
    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://api.fitbit.com/1/';

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
     * Setup a FitBit provider
     * @param string $id       The client ID
     * @param string $secret   The client secret
     * @param string $callback The callback URL
     * @param array  $scope    The scope wanted
     */
    public function __construct($id, $secret, $callback, $scope)
    {
        parent::__construct($id, $secret, $callbak, $scope);

        $base64 = base64_encode($this->id . ':' . $this->secret);
        $this->tokenHeaders['Authorization'] = 'Basic ' . $base64;
    }
}
