<?php
session_start();
error_reporting(0);
// if user is not logged in redirect to index page.
if (!isset($_SESSION['status'])) {
    header('Location: index.php');
}
// if user wants to logout, clear sessions and redirect to index page.
if (isset($_GET["reset"]) && $_GET["reset"] == 1) {
    session_destroy();
    header('Location: ./index.php');
}

require_once './config/constants.php';
require_once './class/function.class.php';
require_once './config/twconfig.php';
require_once './lib/twitteroauth/twitteroauth.php';

$fn_obj = new functions();

// Twitter Oauth Coonection with oauth token and oauth secret
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['request_vars']['oauth_token'], $_SESSION['request_vars']['oauth_token_secret']);

// getting logged in user's detail
$user_details = $connection->get('users/show', array('screen_name' => $_SESSION['request_vars']['screen_name'], 'include_entities' => 'false'));

// getting logged in user'sSITE_URL home timeline tweets
$tweets = $connection->get('statuses/home_timeline', array('screen_name' => $_SESSION['request_vars']['screen_name'], 'include_entities' => 'true', 'count' => TWEET_LIMIT));

// getting logged in user's followers
$followers = array();
$list = "";
$cursor = -1;
do {
    $list = $connection->get('followers/list', array('screen_name' => $_SESSION['request_vars']['screen_name'], 'cursor' => $cursor));
    if (isset($list->error)) {
        break;
    }
    $cursor = $list->next_cursor_str;
    for ($n = 0; $n < sizeof($list->users); $n++) {
        array_push($followers, $list->users[$n]);
    }
} while ($cursor != 0);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Sign-in with Twitter</title>
        <?php
        //loas css files.
        echo $fn_obj->styles('bootstrap/bootstrap.css,select2/select2.css,other/custom.css');
        ?>
        <?php header('Content-Type: text/html; charset=utf-8'); ?>
        <style>
            div#spinner {
                display: none;
                position: fixed;
                top: 0;
                padding-top: 22%;
                left: 0;
                text-align:center;
                z-index:2;
                background:rgba(0,0,0,0.5);
                width: 100%;
                height: 100%;
            }
        </style>
        <link rel="icon" href="images/favicon.ico" type="image/x-icon"/>
    </head>
    <body>
        <div id="spinner">
            <img src="images/ajax-loader.gif" alt="Loading..."/>
        </div>
        <input type="hidden" value="home" name="tweet_type" id="tweet_type" />
        <div class="container">
            <div id="user_div" class="row">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row user_info_detail">
                            <div class="col-lg-3" style="padding: 5px; margin-top: 3px;">
                                Welcome : <label><?php echo $user_details->name; ?></label><?php echo " (@" . $user_details->screen_name . ")"; ?>
                            </div>
                            <div class="col-lg-3 text-center" style="padding: 5px; margin-top: 3px;">
                                <?php if (isset($user_details->location) && $user_details->location != '') { ?>
                                    <i class="glyphicon glyphicon-map-marker"></i>
                                    <?php echo $user_details->location; ?>
                                <?php } ?>
                            </div>
                            <div class="col-lg-3 text-right" style="padding: 5px; margin-top: 3px;">
                                <span class="glyphicon glyphicon-time" title="Joined On"></span>
                                <?php echo date('D, j M Y', strtotime($user_details->created_at)); ?>
                            </div>
                            <div class="col-lg-3 text-right" style="padding: 5px; margin-top: 3px;">
                                <a href="?reset=1">
                                    <img src="images/btn-twitter-logout.png" />
                                </a>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="thumbnail">
                                    <img style="height: 265px;" src="<?php echo str_replace("_normal", "", $user_details->profile_image_url); ?>" alt="Profile Image" />
                                </div>
                            </div>
                            <div class="col-lg-9">
                                <div class="thumbnail">
                                    <?php if (isset($user_details->profile_banner_url) && $user_details->profile_banner_url != '') { ?>
                                        <img style="height: 265px; width: 100%;"  src="<?php echo $user_details->profile_banner_url . "/web"; ?>" alt="Profile Banner Image" />
                                    <?php } else { ?>
                                        <img style="height: 265px; width: 100%;"  src="images/twitter_banner.jpg" alt="Twitter Banner Image" />
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="background-color: #F5F5F5; border: 1px solid #DDDDDD; margin-bottom: 20px; margin-left: 0px; margin-right: 0px; border-radius: 5px;">
                            <div class="col-lg-4 text-left" style="padding: 5px; margin-top: 3px;">
                                <label><?php echo "TWEETS : "; ?></label><?php echo " " . $user_details->statuses_count; ?>
                            </div>
                            <div class="col-lg-4 text-center" style="padding: 5px; margin-top: 3px;">
                                <label><?php echo "FOLLOWING : "; ?></label><?php echo " " . $user_details->friends_count; ?>
                            </div>
                            <div class="col-lg-4 text-right" style="padding: 5px; margin-top: 3px;">
                                <label><?php echo "FOLLOWERS : "; ?></label><?php echo " " . $user_details->followers_count; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="tweets">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Home Timeline Tweets

                        <div class="row pull-right" style="margin-top: -5px; margin-right: 10px; padding-right: 10px;" id="download_div">
                            <select name="formats" id="formats" style="min-width: 100px;">
                                <option value="">Select Export Type</option>
                                <option value="generate_tweets_csv.php">CSV</option>
                                <option value="generate_tweets_xls.php">XLS</option>
                                <option value="generate_tweets_pdf.php">PDF</option>
                            </select>
                            <input type="button" class="btn btn-primary input-sm" name="download" value="Download" style="padding-top: 4px;" onclick="download_tweets();" />
                        </div>
                        <input type="button" class="btn btn-primary input-sm pull-right hide" name="get_home" id="get_home" value="Get Home Timeline Tweets" style="padding-top: 4px; margin-top: -5px; margin-right: 25px;" onclick="get_home_timeline_tweets('1');" />
                    </div>
                    <div class="panel-body" id="tweet_div">
                        <?php $sr_no = 1; ?>
                        <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
                            <div class="carousel-inner">
                                <?php
                                for ($t = 0; $t < 10; $t++) {
                                    if ($t == 0) {
                                        ?>
                                        <div class="active item">
                                            <?php
                                        } else {
                                            ?>
                                            <div class="item">
                                            <?php } ?>
                                            <div style="min-height: 51px; padding: 9px 12px;">
                                                <div style="margin-left:25px; margin-right:25px;">
                                                    <div class="media">
                                                        <img style="border: 3px solid #fff; border-radius: 10px; padding-right: 20px;" align="left" src="<?php echo $tweets[$t]->user->profile_image_url; ?>"/>
                                                        <div class="media-body">
                                                            <label><?php echo $tweets[$t]->user->name; ?></label>
                                                            &nbsp;&nbsp;@<?php echo $tweets[$t]->user->screen_name; ?>
                                                            <br />
                                                            <?php
                                                            if (strpos($tweets[$t]->text, 'http://') !== false) {
                                                                $http_str = explode('http://', $tweets[$t]->text);
                                                                $http_str_after = explode(' ', $http_str[1], 2);
                                                                $link_http = '<a href="http://' . $http_str_after[0] . '" target="_blank">http://' . $http_str_after[0] . '</a>';
                                                                $tweet_str = $http_str[0] . $link_http . " " . $http_str_after[1];
                                                            } else if (strpos($tweets[$t]->text, 'https://') !== false) {
                                                                $http_str = explode('https://', $tweets[$t]->text);
                                                                $http_str_after = explode(' ', $http_str[1], 2);
                                                                $link_http = '<a href="https://' . $http_str_after[0] . '" target="_blank">https://' . $http_str_after[0] . '</a>';
                                                                $tweet_str .= $http_str[0] . $link_http . " " . $http_str_after[1];
                                                            } else {
                                                                $tweet_str .= $tweets[$t]->text;
                                                            }
                                                            echo $tweet_str;
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        $sr_no++;
                                    }
                                    ?>
                                </div>
                                <a class="left carousel-control" href="#carousel-example-generic" data-slide="prev" style="color: #aaa !important; background: none !important; margin-left: -80px !important; margin-top: -13px;">
                                    <span class="glyphicon glyphicon-chevron-left"></span>
                                </a>
                                <a class="right carousel-control" href="#carousel-example-generic" data-slide="next" style="color: #aaa !important; background: none !important; margin-right: -80px !important; margin-top: -13px;">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" id="followers">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Followers
                            <div class="row pull-right" style="margin-top: -5px; margin-right: 5px; min-width: 200px;">
                                <input type="text" id="txtSearchFollowers" class="form-control input-sm search" name="follower_value" maxlength="45" placeholder="Search Name of Follower" />
                            </div>
                        </div>
                        <div class="panel-body" id="followers_div">
                            <div class="row" id="flwers">
                                <?php $cnt = 1; ?>
                                <?php foreach ($followers as $followers_id) { ?>
                                    <div class="col-lg-4 flwers_holder" id="<?php echo $followers_id->name; ?>" <?php if ($cnt > FOLLOWERS_LIMIT) { ?>style="display: none;"<?php } ?>>
                                        <div class="panel panel-default">
                                            <div class="panel-body" style="padding:0px !important;">
                                                <div class="media" style="padding-left: 10px; padding-top: 10px; padding-bottom: 10px;">
                                                    <a onclick="Get_followers_user_tweets('<?php echo $followers_id->screen_name; ?>', '<?php echo $followers_id->id; ?>');" style="cursor: pointer; text-decoration: none;">
                                                        <img style="border: 3px solid #fff; border-radius: 10px;" align="left" src="<?php echo $followers_id->profile_image_url; ?>"/>
                                                        <div class="media-body">
                                                            &nbsp;&nbsp;<label><?php echo $followers_id->name; ?></label>
                                                            <br />
                                                            &nbsp;&nbsp;@<?php echo $followers_id->screen_name; ?>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    $cnt++;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<?php
//load js files.
echo $fn_obj->js('jquery/jquery-1.11.1.js,bootstrap/bootstrap.js,select2/select2.js,jquery.mobile/jquery.mobile.custom.js');
?>
<script>
    //function to fetch tweets of selected follower
    function Get_followers_user_tweets(nm) {
        $('#spinner').fadeIn('fast');
        $.ajax({
            url: 'latest_followers_data.php',
            type: 'post',
            data: {'screen_name': nm},
            success: function(result) {
                if (result.trim() == 'No') {
                    $('#tweet_div').html('No Tweets Available..!!');
                    $('#download_div').hide();
                } else {
                    $('#tweet_div').html(result.trim());
                    $('#tweet_div').carousel();
                    $('#download_div').show();
                }

                $('#tweet_type').val(nm);
                $('#get_home').removeClass('hide');
                $('#spinner').stop().fadeOut('fast');
            }
        });
    }

    $(document).ready(function() {
        // For follower search on keyup
        $(".search").keyup(function() {
            var str = $(".search").val();
            var cnt = 0;
            var fw_cnt = parseInt('<?php echo FOLLOWERS_LIMIT; ?>');
            $("#flwers .flwers_holder").each(function(index) {
                if ($(this).attr("id")) {
                    if (!$(this).attr("id").match(new RegExp(str, "i"))) {
                        $(this).fadeOut("fast");
                    } else {
                        if (cnt < fw_cnt) {
                            $(this).css('display', 'block');
                            $(this).fadeIn("slow");
                        } else {
                            $(this).css('display', 'none');
                        }
                        cnt++;
                    }
                }
            });
        });

        $('select').select2();

        setInterval(function() {
            get_home_timeline_tweets('0');
        }, 600000);
        //set touch swipe effect
        $("#carousel-example-generic").swiperight(function() {
            $("#carousel-example-generic").carousel('prev');
        });
        $("#carousel-example-generic").swipeleft(function() {
            $("#carousel-example-generic").carousel('next');
        });

    });
    //download selected tweets in selected format.
    function download_tweets() {
        var file_type = $('#formats').val();
        if (file_type == '') {
            alert("Please Select Export Type");
        } else {
            $('#spinner').fadeIn('fast');
            var tweet_type = $('#tweet_type').val();
            $.ajax({
                type: 'post',
                url: file_type,
                data: {'tweet_type': tweet_type},
                success: function(result) {
                    document.location = 'file_download.php?filename=' + result.trim();
                    $('#formats').select2('val', '');
                    $('#spinner').stop().fadeOut('fast');
                }
            });
        }
    }
    //funection to get tweets from home timeline
    function get_home_timeline_tweets(spin) {
        if (spin == '1') {
            $('#spinner').fadeIn('fast');
            $('#tweet_type').val('home');
        }
        if ($('#tweet_type').val() == 'home') {
            $.ajax({
                url: 'latest_twit_data.php',
                type: 'post',
                success: function(result) {
                    $('#tweet_div').html(result.trim());
                    $('#tweet_div').carousel();
                    $('#get_home').addClass('hide');
                    $('#download_div').show();
                    $('#tweet_type').val('home');
                    $('#spinner').stop().fadeOut('fast');
                }
            });
        }
    }
</script>