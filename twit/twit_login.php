<?php

session_start();
// Twitter Oauth Configuration
require_once './config/twconfig.php';
require_once './lib/twitteroauth/twitteroauth.php';

// if token is old, distroy any session and redirect user to index.php
if (isset($_REQUEST['oauth_token']) && $_SESSION['token'] !== $_REQUEST['oauth_token']) {
    session_destroy();
    header('Location: ./index.php');
}
// everything looks good, request access token
//successful response returns oauth_token, oauth_token_secret, user_id, and screen_name
elseif (isset($_REQUEST['oauth_token']) && $_SESSION['token'] == $_REQUEST['oauth_token']) {
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['token'], $_SESSION['token_secret']);
    $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
    if ($connection->http_code == '200') {

        //redirect user to twitter
        $_SESSION['status'] = 'verified';
        $_SESSION['request_vars'] = $access_token;

        // unset no longer needed request tokens
        unset($_SESSION['token']);
        unset($_SESSION['token_secret']);
        header('Location: ./home.php');
    } else {
        die("error, try again later!");
    }
} else {
    //fresh authentication
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
    $request_token = $connection->getRequestToken(OAUTH_CALLBACK);

    //received token info from twitter
    $_SESSION['token'] = $request_token['oauth_token'];
    $_SESSION['token_secret'] = $request_token['oauth_token_secret'];

    // any value other than 200 is failure, so continue only if http code is 200
    if ($connection->http_code == '200') {
        //redirect user to twitter
        $twitter_url = $connection->getAuthorizeURL($request_token['oauth_token']);
        header('Location: ' . $twitter_url);
    } else {
        print $connection->http_code;
        die("error connecting to twitter! try again later!");
    }
}
?>