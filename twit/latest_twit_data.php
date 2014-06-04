<?php

session_start();
error_reporting(0);

require_once './config/constants.php';
require_once './config/twconfig.php';
require_once './lib/twitteroauth/twitteroauth.php';

// Twitter connection object after successfully authentication
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['request_vars']['oauth_token'], $_SESSION['request_vars']['oauth_token_secret']);
//fetch home timeline tweets for logged in user.
$tweets = $connection->get('statuses/home_timeline', array('screen_name' => $_SESSION['request_vars']['screen_name'], 'include_entities' => 'true', 'count' => TWEET_LIMIT));

$sr_no = 1;
// Below code is for creating carousel using ajax call
$tweet_str = '<div id="carousel-example-generic" class="carousel slide" data-ride="carousel">';
$tweet_str .= '<div class="carousel-inner">';
for ($t = 0; $t < 10; $t++) {
    if ($t == 0) {
        $tweet_str .= '<div class="active item">';
    } else {
        $tweet_str .= '<div class="item">';
    }
    $tweet_str .= '<div style="position: relative; min-height: 51px; padding: 9px 12px;">';
    $tweet_str .= '<div style="margin-left:25px; margin-right:25px;">';
    $tweet_str .= '<div class="media">';
    $tweet_str .= '<img style="border: 3px solid #fff; border-radius: 10px; padding-right: 20px;" align="left" src="' . $tweets[$t]->user->profile_image_url . '" />';
    $tweet_str .= '<div class="media-body">';
    $tweet_str .= '<label>';
    $tweet_str .= $tweets[$t]->user->name;
    $tweet_str .= '</label>';
    $tweet_str .= '&nbsp;&nbsp;@' . $tweets[$t]->user->screen_name;
    $tweet_str .= '<br />';
    if (strpos($tweets[$t]->text, 'http://') !== false) {
        $http_str = explode('http://', $tweets[$t]->text);
        $http_str_after = explode(' ', $http_str[1], 2);
        $link_http = '<a href="http://' . $http_str_after[0] . '" target="_blank">http://' . $http_str_after[0] . '</a>';
        $tweet_str .= $http_str[0] . $link_http . " " . $http_str_after[1];
    } else if (strpos($tweets[$t]->text, 'https://') !== false) {
        $http_str = explode('https://', $tweets[$t]->text);
        $http_str_after = explode(' ', $http_str[1], 2);
        $link_http = '<a href="https://' . $http_str_after[0] . '" target="_blank">https://' . $http_str_after[0] . '</a>';
        $tweet_str .= $http_str[0] . $link_http . " " . $http_str_after[1];
    } else {
        $tweet_str .= $tweets[$t]->text;
    }
    $tweet_str .= '</div>';
    $tweet_str .= '</div>';
    $tweet_str .= '</div>';
    $tweet_str .= '</div>';
    $tweet_str .= '</div>';
    $sr_no++;
}
$tweet_str .= '</div>';
$tweet_str .= '<a class="left carousel-control" href="#carousel-example-generic" data-slide="prev" style="color: #aaa !important; background: none !important; margin-left: -80px !important; margin-top: -13px !important">
    <span class="glyphicon glyphicon-chevron-left"></span>
  </a>
  <a class="right carousel-control" href="#carousel-example-generic" data-slide="next" style="color: #aaa !important; background: none !important; margin-right: -80px !important; margin-top: -13px !important">
    <span class="glyphicon glyphicon-chevron-right"></span>
  </a>';
$tweet_str .= '</div>';
echo $tweet_str;
?>