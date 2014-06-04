<?php
session_start();
// Check for whether user is logged in or not
if (isset($_SESSION['status'])) {
    header('Location: home.php');
}
require_once './config/constants.php';
require_once './class/function.class.php';
require_once './config/twconfig.php';
require_once './lib/twitteroauth/twitteroauth.php';
$fn_obj = new functions();
?>
<html>
    <head>
        <title>Sign-in with Twitter</title>
        <?php
        //load css files.
        echo $fn_obj->styles('bootstrap/bootstrap.css,other/custom.css');
        ?>
    </head>
    <body>
        <div class="container login_panel">
            <div class="panel panel-default">
                <div class="panel-heading">Twitter Login</div>
                <div class="panel-body">
                    <img src="images/twit_logo.png" width="50%" />
                    <a href="twit_login.php">
                        <img src="images/tw_login.png" />
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
<?php
//load js files.
echo $fn_obj->js('bootstrap/bootstrap.js');
?>