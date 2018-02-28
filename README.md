# ProAuth

This is a PHP OAuth2 library. It is easy to use. Let me show you.

## Usage

Let's say you wanted to implement a "Login with FaceBook" button. All you need to do is redirect the user to `https://yourdomain.com/login/facebook` and make sure the following code runs when the user redirects there:

    <?php
    
    namespace YourDomain;
    
    require('vendor/autoload.php');
    
    use ProAuth\Providers\FaceBook;
    use ProAuth\Exceptions\ProAuth as ProAuthException;
    
    $fb = new FaceBook(
        '123456789012345', // client id
        'abc123def456abc789def012abc345de', // client secret
        'https://yourdomain.com/login/facebook', // redirect uri (this page)
        ['email', 'user_friends', 'publish_actions'] // scope
    );
    
    try {
        $fb->authorize();
        // $fb->token will now contain the access token
        // any API calls you make are now authorized
    } catch (ProAuthException $e) {
        print_r($e);
        exit();
    }
    
    try {
        $response = $fb->get('me?fields=id,email,first_name');
        // use $response and $fb->token to save a new user in your DB
        // now redirect to dashboard
    } catch (ProAuthException $e) {
        print_r($e);
        exit();
    }
    
So what's happening here? [`authorize()`](#authorize) will redirect the user to FaceBook's servers so they can login there. It will tell FaceBook the URI to redirect to when the user is done. Make sure the redirect URI points back to this page. When FaceBook redirects back to our page, it will include a parameter in the GET request called `code`. The [`authorize()`](#authorize) method will look for that. If it finds it, it will use that code to get an access token from FaceBook without redirecting the user.

This means that all of the code before the call to [`authorize()`](#authorize) will execute twice. Once each time the page loads. So make sure your code isn't doing anything crazy before calling [`authorize()`](#authorize).

### Access Token
After getting the access token, the Provider will use that in any subsequent API calls automatically. If you intend to make API calls on behalf of this user at other times, (during a cron job, for instance) you will need to save the access token so you can use it later.

Here is an example of how you would use a saved access token at a later date:

    <?php

    namespace YourDomain;

    require('vendor/autoload.php');

    use ProAuth\Providers\FaceBook;
    use ProAuth\Exceptions\ProAuth as ProAuthException;

    $fb = new FaceBook(
        '123456789012345', // client id
        'abc123def456abc789def012abc345de', // client secret
        'https://yourdomain.com/login/facebook', // redirect uri (this page)
        ['email', 'user_friends', 'publish_actions'] // scope
    );
    
    // note that there is no reason to wrap this call in a try/catch
    // if you pass a token to the authorize method,
    // it doesn't make any requests
    $token = getTheSavedToken();
    $fb->authorize($token);

    // you are now free to make API calls
    // make sure to wrap these calls in a try/catch
    // if your token is expired, for example, the call will fail

Now let's talk about what you would do if you made an API call at a later date and your token was expired. In the case of FaceBook, you will need to have the user go through the authorization process again.

### Refresh Token
Some providers, however, issue refresh tokens. If you saved the refresh token, and provided it to the ProAuth Provider instance, ProAuth will automatically try to refresh the token for you if the access token is expired. Here is an example of what that might look like:

    // .................. //
    // ... login code ... //
    // .................. //

    <?php

    namespace YourDomain;

    require('vendor/autoload.php');

    use ProAuth\Providers\Google;
    use ProAuth\Exceptions\ProAuth as ProAuthException;

    $g = new Google(
        '123-abc456.apps.googleusercontent.com', // client id
        'abc123def456abc789def012', // client secret
        'https://yourdomain.com/login/google', // redirect uri (this page)
        [
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/plus.login'
        ] // scope
    );
    
    try {
        $g->authorize();
        // $g->token will now contain the access token
        // $g->refreshToken will now contain the refresh token
        // save both of these tokens with the user data
    } catch (ProAuthException $e) {
        print_r($e);
        exit();
    }
    
    // ................................ //
    // ... code that executes later ... //
    // ................................ //
    
    <?php

    namespace YourDomain;

    require('vendor/autoload.php');

    use ProAuth\Providers\Google;
    use ProAuth\Exceptions\ProAuth as ProAuthException;

    $g = new Google(
        '123-abc456.apps.googleusercontent.com', // client id
        'abc123def456abc789def012', // client secret
        'https://yourdomain.com/login/google', // redirect uri (this page)
        [
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/plus.login'
        ] // scope
    );
    
    // again, there is no reason to wrap this call in a try/catch
    $token = getTheSavedToken();
    $refreshToken = getTheSavedRefreshToken();
    $g->authorize($token, $refreshToken);
    
    // if the access token is expired, ProAuth will automatically
    // try to refresh it because a refresh token was supplied.
    // if the tokens are successfully refreshed, we want to
    // know about it so we can save the new tokens for later
    // so we supply a callback to onTokenUpdate
    $g->onTokenUpdate(function ($token, $refreshToken) {
        // save the new tokens
    });
    
    // you can now make API calls
    // make sure to use try/catch

## Methods
### authorize([string $token[, string $refreshToken]]) <a name="authorize"></a>
This method needs to be called before making any API calls. If no token is provided, it will redirect the user to the provider's login page. Upon being redirected back to the page, this method will acquire the access and refresh tokens accessible via `$provider->token` and `$provider->refreshToken`.

If the token is provided, this method sets the values but doesn't make any other calls.

### delete(string $endpoint[, array $data[, array $headers]])
This method will make a DELETE request to the specified endpoint.

### get(string $endpoint[, array $data[, array $headers]])
This method will make a GET request to the specified endpoint.

### onTokenUpdate(callable $callback)
Supply a callback to this method. It will be executed any time the access or refresh token updates. It will be called like `$callback($token, $refreshToken)`.

### patch(string $endpoint[, array $data[, array $headers]])
This method will make a PATCH request to the specified endpoint.

### post(string $endpoint[, array $data[, array $headers]])
This method will make a POST request to the specified endpoint.

### prompt([array $data])
This method can be used in place of [`authorize()`](#authorize) if you want to make sure the user goes through the login process. (Note the provider may automatically redirect them if they are already logged in.)

### put(string $endpoint[, array $data[, array $headers]])
This method will make a PUT request to the specified endpoint.

### refresh()
If a refresh token has been set, calling this method will try to refresh the access token.