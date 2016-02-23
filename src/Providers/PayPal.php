<?php

namespace ProAuth\Providers;

class PayPal extends \ProAuth\OAuth2
{
    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://api.paypal.com/v1/oauth2/token';

    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://api.paypal.com/v1/';

    /**
     * Headers to include when getting token (when connecting to token endpoint)
     * @var array
     */
    public $tokenHeaders = [
        'Content-Type' => 'application/x-www-form-urlencoded'
    ];

    /**
     * Setup PayPal Provider
     * @param string $id       The client ID
     * @param string $secret   The client secret
     */
    public function __construct($id, $secret)
    {
        $this->id = $id;
        $this->secret = $secret;
    }

    /**
     * Authorize the client (if no token is set, make a request to get one)
     * @param  string $token        The token
     */
    public function authorize($token = '')
    {
        if ($token) {
            $this->token = $token;
        } else {
            $this->CURLOPT_USERPWD = "{$this->id}:{$this->secret}";
            $this->grant('client_credentials');
        }
    }

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
