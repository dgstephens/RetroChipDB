<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



function test_input( $data )
{
    $data = trim( $data );
    $data = stripslashes( $data );
    $data = htmlspecialchars( $data );
    return $data;
}

/*
 * Create a random string
 * @author	XEWeb <>
 * @param $length the length of the string to create
 * @return $str the string
 */
function randomString($length = 6) {
	$str = "";
	$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
	$max = count($characters) - 1;
	for ($i = 0; $i < $length; $i++) {
		$rand = mt_rand(0, $max);
		$str .= $characters[$rand];
	}
	return $str;
}


// GETCOOKIEDATA
// Attempt to retrieve our login state from the "remember" cookie
function getCookieData( $db_conn, $debug )
{
    $debug = 1;

    $user_id = 0; // set user_id to 0 by default

    if( $debug == 1 )
        echo "No user_id, checking for cookie 'remember'<br>";

    list( $selector, $authenticator) = explode( ':', $_COOKIE['remember']);

    if( $debug == 1 )
        echo "selector: " . $selector . " authenticator: " . $authenticator . "<br>";

    $sql = "SELECT * FROM user_cookie_track"
            . " WHERE selector = '" . $selector . "'";

    if( $debug == 1 )
        echo "cookie sql = " . $sql;


    $result = $db_conn->query( $sql );

    $row = $result->fetch_assoc();

    if( hash_equals( $row['token'], hash('sha256', base64_decode( $authenticator))))
    {
        //set user_id in the sesion variable so we know we are logged in
        $_SESSION['user_id'] = $user_id = $row['user_id'];

        if( $debug == 1 )
        {
            echo "<br>Hash/Token checks out<br>";
            echo "SESSION[user_id]: " . $_SESSION['user_id'] . "<br>";
        }

        $user_query = "SELECT user_name, first_name, last_name "
                . "FROM users "
                . "WHERE user_id=" . $row['user_id'];

        if( $debug == 1 )
            echo "user_query: " . $user_query . "<br>";

        $result = $db_conn->query( $user_query );

        $row = $result->fetch_assoc();
        
        // set user_name just for good measure
        $_SESSION["user_name"] = $user_name = $row["user_name"];

        // set first_name in the session variable so we can easily refer to it later
        $_SESSION["first_name"] = $row["first_name"];

        // set last_name in the session variable so we can easily refer to it later
        $_SESSION["last_name"] = $row["last_name"];

        $logged_in = 1;

        // IS THIS ACCOUNT A valid Admin?
        $sql_admin_check = "SELECT admin_flag "
                . "FROM users "
                . "WHERE user_id=" . $_SESSION["user_id"];

        $result = $db_conn->query( $sql_admin_check );
        if( $result->num_rows > 0 )
        {
            $row = $result->fetch_assoc();
            if( $row["admin_flag"] == 1 ) { $_SESSION["admin_user"] = 1; }
        }

        
    }

    return $user_id;
}

///////////////////////////////
// EMAIL FUNCTIONS
///////////////////////////////

// Send invitation
function myx_sendEmail( $first_name, $last_name,$email_address,$retro_url,$invite_user_key )
{
    require '/var/www/myxtape/vendor/autoload.php';
    $mail = new PHPMailer( true );
        
    //Send mail using gmail
    //This works when sending from a gmail account using an "app" password.
    //The account can be a G-organization account, too. However, you must
    //have 2-step authentication enabled so that the "app" password option
    //is available - as app passwords are the only ones that work with this
    //setup.

    /****************************************************
     * GMAIL SETUP
     *
    *****************************************************/ 
     
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->Host = 'smtp.gmail.com'; // sets GMAIL as the SMTP server
    $mail->Port = 587; // set the SMTP port for the GMAIL server
    $mail->SMTPSecure = "tls"; // sets the prefix to the servier    
    $mail->Username = "myxtape.me@gmail.com"; // GMAIL username
    $mail->Password = "pztoiubznvtbwrdy"; // GMAIL password
  
    /****************************************************
     * MIDPHASE SETUP
     
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->Host = 'slmp-550-107.slc.westdc.net'; // sets midphase as the SMTP server
    $mail->Port = 465; // set the SMTP port for the server
    $mail->SMTPSecure = "ssl"; // sets the prefix to the servier    
    $mail->Username = "daniel@geekpower.com"; // username
    $mail->Password = "Spam1God1"; // password
    ****************************************************/
    

    //Email Body
    $mail->AddAddress($email_address, 'RetroChipDB User');
    $mail->setFrom('myxtape.me@gmail.com', 'RetroChipDB');
    $mail->Subject = "You are invited to join the RetroChipDB";
    $mail->isHTML( true );
    $mail->Body = "$first_name $last_name has invited you to join <b>RetroChipDB</b>!"
            . "<p><a href=" . $retro_url . "learn_more.php>Learn more</a> about the RetroChipDB"
            . "<p>"
            . "Click the following link to <a href="
            . $retro_url . "accept_user_invite.php?invite_user_key=" . $invite_user_key . "><b>Accept the Invitation</b></a>";

    try{
        $mail->Send();
        echo "Success! ";
    } catch(Exception $e){
        //Something went bad
        echo "Fail - " . $mail->ErrorInfo;
    }
}

// Send invitation
function myx_requesetInvitation( $email_address )
{
    require '/var/www/myxtape/vendor/autoload.php';
    $mail = new PHPMailer( true );
        
    //Send mail using gmail
    //This works when sending from a gmail account using an "app" password.
    //The account can be a G-organization account, too. However, you must
    //have 2-step authentication enabled so that the "app" password option
    //is available - as app passwords are the only ones that work with this
    //setup.
    //
    //I had this setup to use daniel@myxtape.me through the midphase smtp server
    //but these emails were constantly marked as spam due to authentication errors
    //I will need to look up spf authentication in order to allow these emails
    //to go through.

    /****************************************************
     * GMAIL SETUP
     *
    *****************************************************/ 
     
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->Host = 'smtp.gmail.com'; // sets GMAIL as the SMTP server
    $mail->Port = 587; // set the SMTP port for the GMAIL server
    $mail->SMTPSecure = "tls"; // sets the prefix to the servier    
    $mail->Username = "myxtape.me@gmail.com"; // GMAIL username
    $mail->Password = "pztoiubznvtbwrdy"; // GMAIL password
  
    /****************************************************
     * MIDPHASE SETUP
     
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->Host = 'slmp-550-107.slc.westdc.net'; // sets midphase as the SMTP server
    $mail->Port = 465; // set the SMTP port for the server
    $mail->SMTPSecure = "ssl"; // sets the prefix to the servier    
    $mail->Username = "daniel@geekpower.com"; // username
    $mail->Password = "Spam1God1"; // password
    ****************************************************/
    

    //Typical mail data
    $mail->AddAddress( 'dgstephens@gmail.com', 'MyxTape User');
    $mail->setFrom('myxtape.me@gmail.com', 'MyxTape');
    $mail->Subject = "A human has requested an account";
    $mail->Body = "$email_address has requested an account\n";

    try{
        $mail->Send();
    } catch(Exception $e){
        //Something went bad
        echo "Fail - " . $mail->ErrorInfo;
    }
}

// Send Receipt function
function myx_sendReceipt( $email_address, $order_number, $mix_name, &$song_array, $price )
{
    require '/var/www/myxtape/vendor/autoload.php';
    $mail = new PHPMailer( true );
        
    //Send mail using gmail
    //This works when sending from a gmail account using an "app" password.
    //The account can be a G-organization account, too. However, you must
    //have 2-step authentication enabled so that the "app" password option
    //is available - as app passwords are the only ones that work with this
    //setup.
    //
    //I had this setup to use daniel@myxtape.me through the midphase smtp server
    //but these emails were constantly marked as spam due to authentication errors
    //I will need to look up spf authentication in order to allow these emails
    //to go through.

    /****************************************************
     * GMAIL SETUP
     *
    *****************************************************/ 
     
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->Host = 'smtp.gmail.com'; // sets GMAIL as the SMTP server
    $mail->Port = 587; // set the SMTP port for the GMAIL server
    $mail->SMTPSecure = "tls"; // sets the prefix to the servier    
    $mail->Username = "myxtape.me@gmail.com"; // GMAIL username
    $mail->Password = "pztoiubznvtbwrdy"; // GMAIL password
 
    /****************************************************
     * MIDPHASE SETUP
    
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->Host = 'slmp-550-107.slc.westdc.net'; // sets midphase as the SMTP server
    $mail->Port = 465; // set the SMTP port for the server
    $mail->SMTPSecure = "ssl"; // sets the prefix to the servier    
    $mail->Username = "daniel@geekpower.com"; // username
    $mail->Password = "Spam1God1"; // password
    ****************************************************/ 
    

    //Typical mail data
    $mail->AddAddress($email_address, 'MyxTape User');
    $mail->setFrom('myxtape.me@gmail.com', 'MyxTape');
    $mail->Subject = "Your MyxTape Receipt for order #" . $order_number;
    $mail->Body = "Here is your receipt for your MyxTape Order " . $order_number . "\n"
            . "Order Details\n\n"
            . "Mix Name: " . $mix_name . "\n"
            . "Price: " . $price . "\n"
            . "If you have any questions, please contact us at support@myxtape.me\n"
            . "Thank you for your order! You are a Rock Star.\n\n"
            . "Sincerely,\n"
            . "The MyxTape Team";
    

    try{
        $mail->Send();
        echo "Success! ";
    } catch(Exception $e){
        //Something went bad
        echo "Fail - " . $mail->ErrorInfo;
    }
}

// SUPPORT EMAIL SEND
function myx_supportEmail( $user_f_name, $user_l_name, $user_id, $myx_url, $subject, $message )
{
    require '/var/www/myxtape/vendor/autoload.php';
    $mail = new PHPMailer( true );
        
    //Send mail using gmail
    //This works when sending from a gmail account using an "app" password.
    //The account can be a G-organization account, too. However, you must
    //have 2-step authentication enabled so that the "app" password option
    //is available - as app passwords are the only ones that work with this
    //setup.

    /****************************************************
     * GMAIL SETUP
     *
    *****************************************************/ 
     
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->Host = 'smtp.gmail.com'; // sets GMAIL as the SMTP server
    $mail->Port = 587; // set the SMTP port for the GMAIL server
    $mail->SMTPSecure = "tls"; // sets the prefix to the servier    
    $mail->Username = "myxtape.me@gmail.com"; // GMAIL username
    $mail->Password = "pztoiubznvtbwrdy"; // GMAIL password
    
    
    /****************************************************
     * MIDPHASE SETUP
     * 
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true; // enable SMTP authentication
    $mail->Host = 'slmp-550-107.slc.westdc.net'; // sets miphase as the SMTP server
    // $mail->Host = 'web033.dnchosting.com';
    $mail->Port = 465; // set the SMTP port for the server
    $mail->SMTPSecure = "ssl"; // sets the prefix to the servier    
    $mail->Username = "daniel@myxtape.me"; // username
    $mail->Password = "Spam1God"; // password
    *****************************************************/    

    //Typical mail data
    $mail->AddAddress( 'dgstephens@gmail.com', 'MyxTape Daniel');
    $mail->setFrom('myxtape.me@gmail.com', 'MyxTape');
    $mail->Subject = "Support Message from " . $user_f_name;
    $mail->Body = "$user_f_name $user_l_name has sent a support message\n"
            . "subject: " . $subject ."\n" 
            . "message: " . $message . "\n";

    try{
        $mail->Send();
        // echo "Success! ";
    } catch(Exception $e){
        //Something went bad
        echo "Fail - " . $mail->ErrorInfo;
    }
}

function set_php_vars( &$vars )
{
    foreach( $vars as &$value )
    {
        // define the variable as global so it keeps the assignment
        global ${$value};
        if( empty(${$value})) 
        { 
            ${$value} = ""; // set the variable to an empty value
        } 
    }
}


////////////////////
// SESSION FUNCTIONS
////////////////////


///////////////////////////////////
// SET SESSION VARS
// example:
// $session_vars = array( "pal_admin_user", "pal_user_id" );
// set_session_vars( $session_vars );

function set_session_vars( &$vars )
{
    foreach( $vars as &$value )
    {
        // This variable equate is slightly different that the one above since we only need
        // to set the SESSION variable name within the SESSION variable brackets in order
        // to reference this global variable - THIS may be better as !isset
        if( empty($_SESSION["$value"])) 
        {
            $_SESSION["$value"] = 0; // set the variable to an empty value
        }
    }
}

function set_get_vars( &$vars )
{
    foreach( $vars as &$value )
    {
        // This variable equate is slightly different that the one above since we only need
        // to set the SESSION variable name within the SESSION variable brackets in order
        // to reference this global variable - THIS may be better as !isset
        if( empty($_GET["$value"])) 
        {
            $_GET["$value"] = 0; // set the variable to an empty value
        }
    }
}

function random_song_year()
{
    // Array of years
    $years = array( "1950", "1962", "1968", "1972", "1980", "1991", "2005", "2017" );
    $rand = mt_rand(0, sizeof( $years )-1 );
    return $years[ $rand ];
}


function my_session_start() {
    // session_start(); // I call this directly at the top of each php file already
    if (isset($_SESSION['destroyed'])) {
       if ($_SESSION['destroyed'] < time()-300) {
           // Should not happen usually. This could be attack or due to unstable network.
           // Remove all authentication status of this users session.
           remove_all_authentication_flag_from_active_sessions($_SESSION['userid']);
           throw(new DestroyedSessionAccessException);
       }
       if (isset($_SESSION['new_session_id'])) {
           // Not fully expired yet. Could be lost cookie by unstable network.
           // Try again to set proper session ID cookie.
           // NOTE: Do not try to set session ID again if you would like to remove
           // authentication flag.
           session_commit();
           session_id($_SESSION['new_session_id']);
           // New session ID should exist
           session_start();
           return;
       }
   }
}

function my_session_regenerate_id() {
    // New session ID is required to set proper session ID
    // when session ID is not set due to unstable network.
    $new_session_id = session_create_id();
    $_SESSION['new_session_id'] = $new_session_id;
    
    // Set destroy timestamp
    $_SESSION['destroyed'] = time();
    
    // Write and close current session;
    session_commit();

    // Start session with new session ID
    session_id($new_session_id);
    ini_set('session.use_strict_mode', 0);
    session_start();
    ini_set('session.use_strict_mode', 1);
    
    // New session does not need them
    unset($_SESSION['destroyed']);
    unset($_SESSION['new_session_id']);
}

//////////////////////////////////////////
// DATABASE QUERIES
//////////////////////////////////////////

// GET ADDRESSES - return address array
function getAddresses( $db_connection, $user_id)
{
    $debug = 0;

    $sql = "SELECT *"
            . " FROM myx_addresses"
            . " WHERE user_id = " . $user_id;

    if( $debug == 1 ) { echo $sql . "<br>"; }

    $result = $db_connection->query( $sql );

    for( $all_addresses_array = array (); $row = $result->fetch_assoc(); $all_addresses_array[] = $row );

    return $all_addresses_array;
}




?>
