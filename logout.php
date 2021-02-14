<?php
// Start the session
session_start();
include 'myx_vars.php';
include 'myx_functions.php';
include 'debug_code.php';
$debug=0;

// destroy the cookie for this user. Must be done before <html> tag
setcookie("myx_user_id", "", time() - 3600);
setcookie("PHPSESSID", "", time() - 3600);

// destroy session
$_SESSION=array();
if( $debug == 1 )
{
    echo "<pre>Session Variables\n";
    var_dump($_SESSION);
    echo "COOKIES\n";
    var_dump($_COOKIE);
    echo"</pre>";
}
session_unset();
session_destroy();
?>
<!-- Copyright 2017 MyxTape -->
<!DOCTYPE html>

<html>
    <head>
        <link REL="icon" HREF="favicon.ico">
        <link rel="stylesheet" type="text/css" href="myxstyle.css?<?php echo time(); ?>"> 
        <meta charset="UTF-8">
        <title>MyxTape</title>
        <style>
            html{
                background-image: url('img/solo_tape_white.png');
                background-repeat: no-repeat;
                background-attachment: fixed;
                background-position: center;
            }
        </style>
    </head>
    <body>
<?php

    echo "Goodbye, farewell, and hope to see you soon.<br>";
    echo "You may <a href=\"" . $myx_url . "index.php\"><b>login</b></a> again<br>";
?>    
        
    </body>
</html>
