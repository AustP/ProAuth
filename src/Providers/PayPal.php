<?php

namespace ProAuth\Providers;

class PayPal extends \ProAuth\OAuth2
{
    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://api.paypal.com/v1/';

    /**
     * The default Content-Type header to use
     * @var string
     */
    protected $defaultContentType = 'application/json';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://api.paypal.com/v1/oauth2/token';

    /**
     * Setup PayPal Provider
     * @param string $id       The client ID
     * @param string $secret   The client secret
     */
    public function __construct($id, $secret)
    {
        parent::__construct($id, $secret, '', []);

        $this->CURLOPT_USERPWD = "{$this->id}:{$this->secret}";
    }

    /**
     * PayPal doesn't support authorization_code grants
     * so instead of prompting, execute the client_credentials grant
     * @param array $data Unused
     */
    public function prompt($data = [])
    {
        $this->grant('client_credentials');
    }

    /**
     * Puts the provider into sandbox mode
     */
    public function sandbox()
    {
        $this->apiEndpoint = 'https://api.sandbox.paypal.com/v1/';
        $this->tokenEndpoint = 'https://api.sandbox.paypal.com/v1/oauth2/token';
    }
}
