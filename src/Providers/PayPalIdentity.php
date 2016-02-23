<?php

namespace ProAuth\Providers;

class PayPalIdentity extends \ProAuth\OAuth2
{
    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://api.paypal.com/v1/identity/openidconnect/tokenservice';

    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://api.paypal.com/v1/identity/openidconnect/';

    /**
     * Headers to include when getting token (when connecting to token endpoint)
     * @var array
     */
    public $tokenHeaders = [
        'Content-Type' => 'application/x-www-form-urlencoded'
    ];

    /**
     * Makes a curl connection
     */
    protected function curl($method, $url, $data = [], $headers = [])
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        return parent::curl($method, $url, $data, $headers);
    }
}
