<?php
session_start();
// login Version .9
// last modified 140221
// modified by: dgs
// TODO
// 1. Show mixes that were made for, or shared with this user.
//
// HTML header is after checking login credentials 
// This is done to allow us to reset the session id
// as it must be reset upon successful login and it can
// not be called after HTML data has been sent to the browser.

$debug=0;

include 'retro_functions.php';
include 'retro_vars.php';
include 'debug_code.php';

// SET VARS
$num_mixes = 0;

// SETUP OUR CONNECTION WITH THE DATABASE
$conn = new mysqli( $servername, $username, $password, $dbname );

// check connection
if( $conn->connect_error  )
{
    die( "Connection failed: " . $conn->connect_error );
}


// set the default client character set
mysqli_set_charset( $conn, 'utf-8' );


// if we have just entered our login credentials
// or we are already logged in, then do this:
if( $_SERVER["REQUEST_METHOD"] == "POST" || $_SESSION["user_id"] > 0 )
{
    
    // if we have just entered our login credentials
    // but we have not been authenticated, then do this:
    if( $_SESSION["user_id"] < 1 )
    {
        // get our user information
        $user = mysqli_real_escape_string( $conn, htmlentities( $_POST["username"] ));
        $password = mysqli_real_escape_string( $conn, htmlentities( $_POST["password"] ));

        $sql = "SELECT user_id, first_name, last_name, password " 
                . "FROM users "
                . "WHERE user_name='" . $user . "'";

        if( $debug == 1 )
            echo "<br>" . $sql . "<br>";

        $result = $conn->query( $sql );

        if( $result->num_rows <= 0 ) // there is no username that matches the one entered
        {
            $_SESSION["user_id"] = 0;         
            include 'error_header.php';
            
            echo "<p align=center>" . $_POST["username"] 
                    . " is not found or you've entered an incorrect password.<br>Please <a href=\"" 
                    . $retro_url . "index.php\"><b>login</b></a> again.</p>";
            
            if( $debug == 1){ echo "<br>debug: The username was not found<br>\n"; }
            
            exit();
        }
        $row = $result->fetch_assoc(); 

        
        if( !password_verify( $password, $row["password"]) )
        {                  
            include 'error_header.php';
            
            echo "<p align=center>" . $_POST["username"] 
            . " is not found or you've entered an incorrect password.<br>Please <a href=\"" 
            . $retro_url . "index.php\"><b>login</b></a> again.</p>";

            if( $debug == 1){ echo "<br>debug: The password was incorrect<br>\n"; }
            
            exit();

        }

        //GENERATE NEW SESSION ID UPON SUCCESSFUL LOGIN
        // my_session_start();
        // session_regenerate_id();
        
        // set user_id in the session variable so we know we are logged in
        $_SESSION["user_id"] = $row[ "user_id" ];

        // set first_name in the session variable so we can easily refer to it later
        $_SESSION["first_name"] = $row[ "first_name" ];

        // set last_name in the session variable os we can easily refer to it later
        $_SESSION["last_name"] = $row[ "last_name" ];
            
        // IS THIS ACCOUNT an Admin?
        $sql = "SELECT admin_flag "
                . "FROM users "
                . "WHERE user_id=" . $_SESSION["user_id"];

        $result = $conn->query( $sql );
        $row = $result->fetch_assoc();
        
        if( $debug == 1 ) 
        { 
            echo "admin_flag: " . $row["admin_flag"] . "<br>"; 
            print_r( $_SESSION );
        }
        
        if( $row["admin_flag"] == 1 ) { $_SESSION["admin_user"] = 1; }
        
        // insert login action into database
        $track_user = mysqli_query( $conn, "INSERT INTO user_login_track (user_id) "
                . "VALUES (" . $_SESSION["user_id"] . ")" );  

        // insert cookie data into database and share with web client
	//
	$selector = base64_encode( openssl_random_pseudo_bytes( 9 ));
        $authenticator = openssl_random_pseudo_bytes( 33 );

        // set the cookie in the web client
        // NO TEXT OUTPUT CAN COME PRIOR TO THIS COMMAND - including any DEBUGGING TEXT
        setcookie( "remember", $selector . ":" . base64_encode( $authenticator ), time() + 1209600, "/", "retrochipdb.com", false, true );

        if( $debug == 1 )
        {
            echo "selector: " . $selector . "<br>";
            echo "authenticator: " . $authenticator . "<br>";
        }

        $sql = "INSERT INTO user_cookies"
                . " ( user_id, selector, token, expires )"
                . " VALUES ( '" . $_SESSION["user_id"] . "', '" . $selector . "', '" 
                . hash('sha256', $authenticator) . "', '" . date('Y-m-d H:i:s', time() + 864000 ) . "')";

        if( $debug == 1 )
            echo "<br>cookie sql = " . $sql;

        $result = $conn->query( $sql );

        // WE ARE LOGGED IN!
        $logged_in = 1;

        if( $debug == 1 )
            {
                echo "admin_flag: " . $row["admin_flag"] . "<br>";
                print_r( $_SESSION );
            }

    }  

    else if( $_SESSION["user_id"] > 0 )
    {
        $logged_in = 1;
    }

} // END CHECK FOR POST ACCESS via LOGIN

// IF WE GOT HERE VIA LINK OR DIRECT URL, CHECK COOKIE CREDS
if( empty( $_SESSION['user_id']) && !empty($_COOKIE['remember']))
{
    $user_id = getCookieData( $conn, 0 );
    if( $user_id > 0 )
    {
        $logged_in = 1;
        header("Refresh:0");
        echo "one moment...";
    }
}

// TEST COOKIES
if( $debug == 1)
{
    echo "<br>PRINT ALL COOKIES\n<br>";
    print_r( $_COOKIE );
    echo "<br>SESSION user_id: " . $_SESSION["user_id"] . "<br>";
    echo "<br>SESSION user_f_name: " . $_SESSION["first_name"] . "<br>";
}

    
?>
<!DOCTYPE html>
<!-- Copyright 2021 geekpower -->
<html>
    <head>
        <link rel="shortcut icon" href="http://retrochipdb/favicon.ico" />
        <link rel="stylesheet" type="text/css" href="retrostyle.css?<?php echo time(); ?>"> 
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, minimal-ui">
        <meta charset="UTF-8">
        <title>RetroChipDB</title>
        <style>
            .column {
                float: left;
                width: 20%;
                padding: 5px;
            }
            .row:after {
                content: "";
                display: table;
                clear: both;
            }
                    
  
            @media only screen and (max-width: 1320px) {    
                .column {
                    width: 30%;
                }
            }
            @media only screen and (max-width: 950px) {    
                .column {
                    width: 40%;
                }
            } 
            @media only screen and (max-width: 680px) {    
                .column {
                    width: 50%;
                }
            }
        </style>
    </head>
    <body>        
<?php

if( $logged_in  == 1 )
{

    ////////////////////////////////////////////
    // MENU BUTTON and HEADER TITLE
    ////////////////////////////////////////////
    include 'pulldown_menu_button.php';
    echo "<div class=\"mainDiv\">"; //start main div

    echo "<div class=\"news\">";
    echo "<b>News</b>&nbsp14 February, 2021<p>";
    echo "We are working on making your day just a little brighter.<br>"
        . "Right now we are adding inventory functionality to the database!";

    echo "</div><p>";
    
    $sql = mysqli_query( $conn, "SELECT project_name, project_id "
            . "FROM my_projects "
            . "WHERE user_id = " . $_SESSION["user_id"] );
    
    echo "<b>Projects you've created</b><p>";    
    if( mysqli_num_rows( $sql ) > 0 )
    {
        echo "<div class=row>";
        while( $row = mysqli_fetch_assoc( $sql ))
        {
            echo "<div class=column>";
            echo "  <div class=mix_container>";
            echo "      <img src=img/project_icon.png>";
            echo "      <div class=mix_text_block>";
            echo "          <a class=projects href=\"" . $retro_url . "add_songs.php?mix_id=" . $row["project_id"] . "\">" 
                                . $row["project_name"] . "</a> &nbsp &nbsp";
            echo "      </div>"; // close text block
            echo "  </div>"; // close container
            echo "</div>"; // close column
            $num_mixes = $num_mixes + 1;
        }
        echo "</div>"; // close row
        echo "<p>";
    }
    else
    {
        echo "You haven't started any projects yet! (Sad Face)<br>";
        echo "Select \"New Project\" below to create a new project<br>";
        echo "And then add chips to it. Happiness!";
    }
       
    // COULD ALSO SHOW PROJECTS THAT WERE SHARED WITH THIS USER
 
    // close the connection
    mysqli_close( $conn );
    if( $debug == 1 ) { include 'debug_code.php'; }
    
    
    echo "</div>"; // end mainDiv
    // MENU
    include 'menu_include.php';
}

// if we are not already logged in, then present the login screen
else 
{
    // NOT LOGGED IN
    echo "You are not logged in.<br>\n";
    echo "Please continue to the <a href=\"" . $retro_url . "index.php\"><b>login screen</b>.<br>\n";
}
?>    
        
    </body>
</html>
