<?php

namespace ProAuth\Providers;

class PayPalIdentity extends \ProAuth\OAuth2
{
    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://api.paypal.com/v1/identity/openidconnect/';

    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize';

    /**
     * The default Content-Type header to use
     * @var string
     */
    protected $defaultContentType = 'application/json';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://api.paypal.com/v1/identity/openidconnect/tokenservice';
}
