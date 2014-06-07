<?php

header('Content-Type: text/html; charset=utf-8');
session_start();
//Twitter authentication required file
require_once './config/constants.php';
require_once './config/twconfig.php';
require_once './lib/twitteroauth/twitteroauth.php';
require_once './lib/mpdf/mpdf.php';

// Twitter connection object after successfully authentication
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['request_vars']['oauth_token'], $_SESSION['request_vars']['oauth_token_secret']);

// Checking the tweets are of user's home timeline or Follower's timeline
if ($_REQUEST['tweet_type'] == 'home') {
    $tweets = $connection->get('statuses/home_timeline', array('screen_name' => $_SESSION['request_vars']['screen_name'], 'include_entities' => 'true', 'count' => TWEET_LIMIT));
    $title_pdf = 'Tweets of Home Timeline';
} else {
    $tweets = $connection->get('statuses/user_timeline', array('screen_name' => $_REQUEST['tweet_type'], 'include_entities' => 'true', 'count' => TWEET_LIMIT));
    $title_pdf = 'Tweets of ' . $_REQUEST['tweet_type'];
}

$filepath = $_SERVER['DOCUMENT_ROOT'] . "/twit/download";

if (!file_exists($filepath)) {
    mkdir($filepath, 0777, true);
}
// path to save download file
$filename = $_SERVER['DOCUMENT_ROOT'] . "/twit/download/" . $_REQUEST['tweet_type'] . "_tweets_" . time() . ".pdf";

if (sizeof($tweets) > 10) {
    $limit_tweets = 10;
} else {
    $limit_tweets = sizeof($tweets);
}

ob_clean();

$tweet_str .= '<body>';
$tweet_str .= '<table cellspacing="40" cellpadding="10">';

for ($t = 0; $t < $limit_tweets; $t++) {
    $tweet_str .= '<tr>';
    $tweet_str .= '<td style="text-align: center">';
    $tweet_str .= '<img src="' . $tweets[$t]->user->profile_image_url . '" />';
    $tweet_str .= '</td>';
    $tweet_str .= '<td valign="top">';
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
    $tweet_str .= '</td>';
    $tweet_str .= '</tr>';
}
$tweet_str .= '</table>';
$tweet_str .= '</body>';

//Create new pdf file
$mpdf = new mPDF('utf-8', 'A4', '', '', 10, 10, 40, 20, 10, 10);

$mpdf->SetHTMLHeader('<div style="padding-bottom: 20px; border-bottom: 1px solid #000000;"><div style="text-align:left; width: 100px; float: left;"><img src="images/twitter-icon.png" width="70" height="70" /></div><div style="text-align: right; font-weight: bold; font-size: 25; float: left; padding-top: 25px;">' . $title_pdf . '</div></div><br />');
$mpdf->SetHTMLFooter('<br /><div style="text-align: right; font-weight: bold; border-top: 1px solid #000000;">{PAGENO}/{nbpg}</div>');
//write data content into file
$mpdf->WriteHTML($tweet_str);
//open new creted file into browser
$mpdf->Output($filename, "F");
echo $filename;
?>