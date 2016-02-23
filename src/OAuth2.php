<?php

namespace ProAuth;

abstract class OAuth2
{
    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = '';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = '';

    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = '';

    /**
     * The client ID
     * @var string
     */
    protected $id = '';

    /**
     * The client secret
     * @var string
     */
    protected $secret = '';

    /**
     * The callback URL
     * @var string
     */
    protected $callback = '';

    /**
     * The scope wanted
     * @var array
     */
    protected $scope = [];

    /**
     * The token
     * @var string
     */
    public $token = '';

    /**
     * The refresh token
     * @var string
     */
    public $refreshToken = '';

    /**
     * Headers to include when getting token (when connecting to token endpoint)
     * @var array
     */
    protected $tokenHeaders = [];

    /**
     * The CURLOPT_USERPWD to use when making curl requests
     * @var string
     */
    protected $CURLOPT_USERPWD = '';

    /**
     * The last response received from the endpoint
     * @var array
     */
    public $lastResponse = [];

    /**
     * Boolean indicating if we have tried to refresh the token
     * @var boolean
     */
    public $refreshed = false;

    /**
     * Setup an OAuth2 provider
     * @param string $id       The client ID
     * @param string $secret   The client secret
     * @param string $callback The callback URL
     * @param array  $scope    The scope wanted
     */
    public function __construct($id, $secret, $callback, $scope)
    {
        $this->id = $id;
        $this->secret = $secret;
        $this->callback = $callback;
        $this->scope = $scope;
    }

    /**
     * Authorize the user (if no token is set, it will be user directed)
     * @param  string $token        The token
     * @param  string $refreshToken The refresh token
     */
    public function authorize($token = '', $refreshToken = '')
    {
        if ($token) {
            $this->token = $token;
            $this->refreshToken = $refreshToken;
        } else {
            $this->prompt();
        }
    }

    /**
     * Makes a curl connection
     * @param  string $method  The HTTP method to use ('get' | 'post')
     * @param  string $url     The URL to connect to
     * @param  array  $data    Data to use in the connection
     * @param  array  $headers Headers to use in the connection
     * @return array
     */
    protected function curl($method, $url, $data = [], $headers = [])
    {
        if ($url != $this->tokenEndpoint) {
            $url = $this->apiEndpoint . $url;
        }

        if ($data) {
            if ($method == 'post' && isset($headers['Content-Type'])
            && $headers['Content-Type'] == 'application/json') {
                $data = json_encode($data);
            } else {
                $data = http_build_query($data);
            }
        }

        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            ($method == 'get' && $data) ? ($url . '?' . $data) : $url
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $headers['Accept'] = 'application/json';

        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            $headers['Content-Length'] = strlen($data);
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }
        }

        if ($this->CURLOPT_USERPWD) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->CURLOPT_USERPWD);
        }

        if ($this->token && !isset($headers['Authorization'])) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "$key: $value";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        $error = curl_error($ch);
        $output = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // attempt to refresh the token and try again
        if ($code == 401 && !$this->refreshed) {
            $this->refreshed = true;

            try {
                $this->refresh();
                return $this->curl(...func_get_args());
            } catch (Exceptions\ProAuth $e) {
                // if the refresh failed,
                // let the original request throw the exception
            }
        }

        $response = json_decode($output, true);
        $this->lastResponse = $response;

        if (isset($response['errors']) && count($response['errors'])) {
            throw new Exceptions\ProAuth($response['errors']);
        }

        if ($code < 200 || $code >= 300) {
            $errors = [
                ['message' => $url . ' responded with code: ' . $code],
                ['message' => $output]
            ];

            if ($error) {
                $errors[] = ['message' => $error];
            }

            throw new Exceptions\ProAuth($errors);
        }

        return $response;
    }

    /**
     * Make a GET connection to the API Endpoint
     * @return array
     */
    public function get(...$args)
    {
        return $this->curl('get', ...$args);
    }

    /**
     * Gets the access and refresh tokens from the token endpoint
     * @param  string $type The grant type to use
     * @param  array  $data Data to send to the endpoint
     * @return array
     */
    protected function grant($type, $data = [])
    {
        $data['client_id'] = $this->id;
        $data['client_secret'] = $this->secret;
        $data['grant_type'] = $type;

        $response = $this->post(
            $this->tokenEndpoint,
            $data,
            $this->tokenHeaders
        );

        if (isset($response['access_token'])) {
            $this->token = $response['access_token'];
        }

        if (isset($response['refresh_token'])) {
            $this->refreshToken = $response['refresh_token'];
        }

        return $response;
    }

    /**
     * Make a POST connection to the API endpoint
     * @return array
     */
    public function post(...$args)
    {
        return $this->curl('post', ...$args);
    }

    /**
     * Redirect the user to the authorization prompt (the provider may
     *     automatically redirect them back if they are logged in)
     * @param array $data Data to authorize with
     */
    public function prompt($data = [])
    {
        if (isset($_GET['error'])) {
            throw new Exceptions\ProAuth($_GET['error']);
        } elseif (isset($_GET['code'])) {
            $this->grant('authorization_code', [
                'code' => $_GET['code'],
                'redirect_uri' => $this->callback
            ]);
        } else {
            $data['client_id'] = $this->id;
            $data['response_type'] = 'code';
            $data['scope'] = implode(' ', $this->scope);
            $data['redirect_uri'] = $this->callback;

            $url = $this->authorizeEndpoint . '?' . http_build_query($data);
            header('Location: ' . $url);
            exit();
        }
    }

    /**
     * Refresh the token
     * @return array
     */
    public function refresh()
    {
        return $this->grant('refresh_token', [
            'refresh_token' => $this->refreshToken
        ]);
    }
}
