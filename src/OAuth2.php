<?php

namespace ProAuth;

abstract class OAuth2
{
    /**
     * The API endpoint
     * @var string
     */
    public $apiEndpoint = '';

    /**
     * The authorization endpoint
     * @var string
     */
    public $authorizeEndpoint = '';

    /**
     * The callback URL
     * @var string
     */
    protected $callback = '';

    /**
     * The CURLOPT_USERPWD to use when making curl requests
     * @var string
     */
    protected $CURLOPT_USERPWD = '';

    /**
     * Whether the provider is in debug mode
     * @var boolean
     */
    public $debugMode = false;

    /**
     * The default Content-Type header to use
     * @var string
     */
    protected $defaultContentType = 'application/x-www-form-urlencoded';

    /**
     * Boolean indicating if we have tried to refresh the token
     * @var boolean
     */
    public $hasRefreshed = false;

    /**
     * The client ID
     * @var string
     */
    protected $id = '';

    /**
     * The last response received from the endpoint
     * @var array
     */
    public $lastResponse = [];

    /**
     * The refresh token
     * @var string
     */
    public $refreshToken = '';

    /**
     * The scope wanted
     * @var array
     */
    protected $scope = [];

    /**
     * The client secret
     * @var string
     */
    protected $secret = '';

    /**
     * The token
     * @var string
     */
    public $token = '';

    /**
     * The access token endpoint
     * @var string
     */
    public $tokenEndpoint = '';

    /**
     * Headers to include when getting token (when connecting to token endpoint)
     * @var array
     */
    protected $tokenHeaders = [
      'Content-Type' => 'application/x-www-form-urlencoded'
    ];

    /**
     * The callback to call when the tokens update
     * @var function
     */
    protected $tokensUpdateCallback = null;

    /**
     * Whether to append a trailing slash to the api calls or not
     * @var boolean
     */
    protected $trailingSlash = false;

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
     * @param  string $method   The HTTP method to use ('get' | 'post')
     * @param  string $url      The URL to connect to
     * @param  array  $data     Data to use in the connection
     * @param  array  $headers  Headers to use in the connection
     * @return array
     */
    protected function curl($method, $url, $data = [], $headers = [])
    {
        // we will keep original copies of the arguments so we can re-call this method
        $args = [$method, $url, $data, $headers];

        if ($url != $this->tokenEndpoint) {
            $url = rtrim($this->apiEndpoint . $url, '/');
            if ($this->trailingSlash) {
                $hasQuestionMark = strpos($url, '?') !== false;
                $hasHash = strpos($url, '#') !== false;

                if (!$hasHash && !$hasQuestionMark) {
                    $url .= '/';
                }
            }
        }

        if ($data) {
           if ($method == 'get') {
               unset($headers['Content-Type']);
               $url .= '?' . http_build_query($data);
           } else {
               if (!isset($headers['Content-Type'])) {
                   $headers['Content-Type'] = $this->defaultContentType;
               }

               // encode data as per our request
               if ($headers['Content-Type'] === 'application/json') {
                   $data = json_encode($data);
               } elseif ($headers['Content-Type'] === 'application/x-www-form-urlencoded') {
                   $data = http_build_query($data);
               }
           }
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $headers['Accept'] = 'application/json';

        if ($method != 'get') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

            if ($data) {
                $headers['Content-Length'] = strlen($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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

        if ($this->debugMode) {
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'handleCurlResponseHeaders']);
            print_r([$method, $url, $headers, $data]);
            print(PHP_EOL);
        }

        $output = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($this->debugMode) {
            print_r($output);
            print(PHP_EOL . PHP_EOL);
        }

        // attempt to refresh the token and try again
        if (($code == 401) && !$this->hasRefreshed) {
            $this->hasRefreshed = true;

            $this->refresh();
            return $this->curl(...$args);
        }

        $response = json_decode($output, true);
        $this->lastResponse = $response;

        if ($this->debugMode && $response) {
            print_r($response);
        }

        if (isset($response['errors']) && count($response['errors'])) {
            throw new Exceptions\ProAuth($response['errors'], 501);
        }

        if ($code < 200 || $code >= 300) {
            $errors = [
                ['message' => $url . ' responded with code: ' . $code]
            ];

            if ($error) {
                $errors[] = ['message' => $error];
            }

            if ($output) {
                $errors[] = ['message' => "Output:\n" . $output];
            }

            throw new Exceptions\ProAuth($errors, $code);
        }

        return $response;
    }

    /**
     * Make a DELETE connection to the API Endpoint
     * @param  string $url     The URL to connect to
     * @param  array  $data    Data to use in the connection
     * @param  array  $headers Headers to use in the connection
     * @return array
     */
    public function delete(...$args)
    {
        return $this->curl('delete', ...$args);
    }

    /**
     * Make a GET connection to the API Endpoint
     * @param  string $url     The URL to connect to
     * @param  array  $data    Data to use in the connection
     * @param  array  $headers Headers to use in the connection
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

        $didTokensUpdate = false;

        if (isset($response['access_token'])) {
            $token = $response['access_token'];
            if ($token != $this->token) {
                $didTokensUpdate = true;
            }

            $this->token = $token;
        }

        if (isset($response['refresh_token'])) {
            $token = $response['refresh_token'];
            if ($token != $this->refreshToken) {
                $didTokensUpdate = true;
            }

            $this->refreshToken = $token;
        }

        if ($didTokensUpdate && $this->tokensUpdateCallback) {
            // we have to use __invoke here in order to call the closure
            $this->tokensUpdateCallback->__invoke($this->token, $this->refreshToken);
        }

        return $response;
    }

    /**
     * To make debugging easier, we pass this as a callback to CURLOPT_HEADERFUNCTION
     * @param object $ch     The current curl object
     * @param string $header The current header
     * @return number
     */
    protected function handleCurlResponseHeaders($ch, $header)
    {
        print($header);
        return strlen($header);
    }

    /**
     * Sets a callback to call when tokens are updated
     * @param  function $callback The callback to call
     * @return void
     */
    public function onTokenUpdate($callback)
    {
        $this->tokensUpdateCallback = $callback;
    }

    /**
     * Make a PATCH connection to the API endpoint
     * @param  string $url     The URL to connect to
     * @param  array  $data    Data to use in the connection
     * @param  array  $headers Headers to use in the connection
     * @return array
     */
    public function patch(...$args)
    {
        return $this->curl('patch', ...$args);
    }

    /**
     * Make a POST connection to the API endpoint
     * @param  string $url     The URL to connect to
     * @param  array  $data    Data to use in the connection
     * @param  array  $headers Headers to use in the connection
     * @return array
     */
    public function post(...$args)
    {
        return $this->curl('post', ...$args);
    }

    /**
     * Make a PUT connection to the API endpoint
     * @param  string $url     The URL to connect to
     * @param  array  $data    Data to use in the connection
     * @param  array  $headers Headers to use in the connection
     * @return array
     */
    public function put(...$args)
    {
        return $this->curl('put', ...$args);
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
        if ($this->refreshToken) {
            return $this->grant('refresh_token', [
                'refresh_token' => $this->refreshToken
            ]);
        }
    }
}
