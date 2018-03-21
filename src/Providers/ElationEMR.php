<?php

namespace ProAuth\Providers;

class ElationEMR extends \ProAuth\OAuth2
{
    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = 'https://app.elationemr.com/api/2.0/';

    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = ''; // not supported

    /**
     * The default Content-Type header to use
     * @var string
     */
    protected $defaultContentType = 'application/json';

    /**
     * The resource owner password
     * @var string
     */
    protected $password;

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = 'https://app.elationemr.com/api/2.0/oauth2/token/';

    /**
     * Whether to append a trailing slash to the api calls or not
     * @var boolean
     */
    protected $trailingSlash = true;

    /**
     * The resource owner username
     * @var string
     */
    protected $username;

    /**
     * Setup an ElationEMR provider
     * @param string $id       The client ID
     * @param string $secret   The client secret
     * @param string $username The resource owner username
     * @param string $password The resource owner password
     */
    public function __construct($id, $secret, $username, $password)
    {
        $this->id = $id;
        $this->secret = $secret;
        $this->username = $username;
        $this->password = $password;

        $base64 = base64_encode($this->id . ':' . $this->secret);
        $this->tokenHeaders['Authorization'] = 'Basic ' . $base64;
    }

    /**
     * Elation doesn't support authorization_code grants
     * so instead of prompting, execute the password grant
     * @param array $data Unused
     */
    public function prompt($data = [])
    {
        $this->grant('password', [
            'username' => $this->username,
            'password' => $this->password
        ]);
    }

    /**
     * The refresh token expires when the access token does
     * so if the access token is expired, we just need to reauthorize
     * @return array
     */
    public function refresh()
    {
        return $this->authorize();
    }

    /**
     * Puts the provider into sandbox mode
     */
    public function sandbox()
    {
        $this->apiEndpoint = 'https://sandbox.elationemr.com/api/2.0/';
        $this->tokenEndpoint = 'https://sandbox.elationemr.com/api/2.0/oauth2/token/';
    }
}
