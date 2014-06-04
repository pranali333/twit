<?php

session_start();
//Twitter authentication required file
require_once './config/constants.php';
require_once './config/twconfig.php';
require_once './lib/twitteroauth/twitteroauth.php';

// Twitter connection object after successfully authentication
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['request_vars']['oauth_token'], $_SESSION['request_vars']['oauth_token_secret']);

// Checking the tweets are of user's home timeline or Follower's timeline
if ($_REQUEST['tweet_type'] == 'home') {
    $tweets = $connection->get('statuses/home_timeline', array('screen_name' => $_SESSION['request_vars']['screen_name'], 'include_entities' => 'true', 'count' => TWEET_LIMIT));
} else {
    $tweets = $connection->get('statuses/user_timeline', array('screen_name' => $_REQUEST['tweet_type'], 'include_entities' => 'true', 'count' => TWEET_LIMIT));
}

// path to save download file
$filename = $_SERVER['DOCUMENT_ROOT'] . "/twit/download/" . $_REQUEST['tweet_type'] . "_tweets_" . time() . ".csv";
$delimiter = ",";

// open file for writing
$f = fopen($filename, "w");

// Set headers in the file
fputcsv($f, array("id_str", "created_at", "text", "name", "screen_name", "profile_image_url"), $delimiter);

// loop over the input array
foreach ($tweets as $line) {
    // generate csv lines from the inner arrays
    fputcsv($f, array("'" . $line->id_str . "'", $line->created_at, $line->text, $line->user->name, $line->user->screen_name, $line->user->profile_image_url), $delimiter);
}
// rewrind the "file" with the csv lines
fseek($f, 0);
// tell the browser it's going to be a csv file
header('Content-Type: application/csv');
// tell the browser we want to save it instead of displaying it
header('Content-Disposition: attachement; filename="' . $filename . '"');
// make php send the generated csv lines to the browser
fpassthru($f);

echo $filename;
?>