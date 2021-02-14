<?php
session_start();
// invite_user Version .4
// last modified 140221
// modified by: dgs
// TODO
// 
$debug=0;
include 'retro_vars.php';
include 'retro_functions.php';
include 'debug_code.php';   

?>
<!-- Copyright 2021 geekpower -->
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <link REL="icon" HREF="favicon.ico">
        <link rel="stylesheet" type="text/css" href="retrostyle.css?<?php echo time(); ?>">
        <meta charset="UTF-8">
        <title>RetroChipDB Invite User</title>
    </head>
    <body>

<?php

// check to see if we are logged in
if( $_SESSION["user_id"] == 0 )
{
    exit( "You don't have permission to be here" );
}


// create connection
$conn = new mysqli( $servername, $username, $password, $dbname );

// check connection
if( $conn->connect_error )
{
    die( "Connection failed: " . $conn->connect_error );
}

////////////////////////////////////
// MENU BUTTON and HEADER TITLE
////////////////////////////////////
include 'pulldown_menu_button.php';
echo "<div class=\"mainDiv\">"; //start main div

echo "<p><b>Invite a Friend</b></P>\n";


if( $_SERVER["REQUEST_METHOD"] == "POST" ) // we came here from submitting this form
{
    $write_to_database = 1;

    if( empty($_POST["email_address"]))
    {
        $email_address_1_err = "Email address is required";
        $write_to_database = 0;
    } else {
        $email_address = test_input( $_POST["email_address"]);
        // check if name is just letters and whitespace
        if( !filter_var($email_address, FILTER_VALIDATE_EMAIL))
        {
            $email_address_err = "This is not a valid email address";
            $write_to_database = 0;
        }
    }
}

// Write this person to the database to invite them to try RetroChipDB

if( $write_to_database == 1 )
{
    // check if this email has already been invited
    $sql_email_check = "SELECT invite_user_id "
            . "FROM user_invite "
            . "WHERE invite_email=\"" . $email_address . "\"";
    
    if( $debug == 1 ) { echo $sql_email_check . "<br>"; }
    
    $result = $conn->query( $sql_email_check );
            
    $row = $result->fetch_assoc();
    
    // if we haven't invited this person yet then we can invite them now
    if( $row["invite_user_id"] < 1 )
    {
        // Generate invite key
        $invite_user_key = randomString( 16 );
        
        $sql = "INSERT INTO user_invite "
                . "(invite_user_key, invite_email, invite_by_user_id )"
                . "VALUES(\"" . $invite_user_key . "\",\"" . $email_address . "\","
                . $_SESSION["user_id"] . ")";

        if( $debug == 1 ) { echo $sql . "<br>"; }

        // check if return result is TRUE
        if( $conn->query( $sql ) === TRUE )
        {
            // send email
            sendEmail( $_SESSION["first_name"],$_SESSION["last_name"],$email_address, $retro_url, $invite_user_key );
            
            echo $email_address . " has been invited to join the fun!<br>";    
            echo "Perhaps you'd like to invite another friend.<br><br>";
            
            $email_address = ""; // reset email address
        }
        else
        {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }        
    }
    else
    {
        echo "<span class=\"orange\">" . $email_address . " has already been invited.</span><br>";
        echo "Try inviting someone else.<br><br>";
        
        $email_address = ""; // reset email address
    }                
}

    if( empty($email_address)) { $email_address = ""; }
    if( empty($email_address_err )) { $email_address_err = ""; }

?>    
        <form method="post" name="invite friend" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            Right now, the only way to sign up for the RetroChipDB is to be invited. This<br>
            is to keep the userbase small while we continue to refine the service<br>
            and work out the bugs. So please, invite a friend to come and play and help<br>
            make the service spiff-tastic.<p>
            Enter the email address of a friend you'd like to invite<br>
            <input type="text" name="email_address" placeholder="Email Address" size="40" value="<?php echo $email_address; ?>" /> <?php echo $email_address_err; ?>         
            <input type="submit" value="Invite a friend" />
        </form>
               
<?php

    // end main Div
    echo "</div>";
    // MENU
    include 'menu_include.php';

$conn->close();


?>
