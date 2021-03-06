<?php
session_start();
// accept_user_invite Version .5
// last modified 140221
// modified by: dgs
// TODO
//
$debug=0;
include 'retro_vars.php';
include 'retro_functions.php';
include 'debug_code.php';  

// SET VARS
$user_name = $user_name_err = $password_1 = $password_1_err = $password_2 = $password_2_err = "";
        
?>
<!-- Copyright 2021 geekpower -->
<!DOCTYPE html>
<html>
    <head>
        <script>
            function check(str) {
                if (str.length == 0) { 
                    document.getElementById("user_name_checker").innerHTML = "";
                    return;
                } else {
                    var xmlhttp = new XMLHttpRequest();
                    xmlhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            document.getElementById("user_name_checker").innerHTML = this.responseText;
                        }
                    };
                    xmlhttp.open("GET", "user_name_available_check.php?user_name=" + str, true);
                    xmlhttp.send();
                }
            }
        </script>        
        <link REL="icon" HREF="favicon.ico">
        <link rel="stylesheet" type="text/css" href="retrostyle.css?<?php echo time(); ?>">
        <meta charset="UTF-8">
        <title>RetroChipDB Invite User</title>
        <style>
            body { 
                background: none; /* overwrite background color from retrostyle.css */
            }
            html{
                background: url( img/solo_tape_white.png ) fixed no-repeat;
                background-size: cover;
            }
            
        </style>
    </head>
    <body>

<?php

include 'debug_code.php';    

echo "<H1><b>RetroChipDB</h1>\n<P>Accept invitation</P></b>\n";


// create connection
$conn = new mysqli( $servername, $username, $password, $dbname );

// check connection
if( $conn->connect_error )
{
    die( "Connection failed: " . $conn->connect_error );
}

// we came here from submitting this form
if( $_SERVER["REQUEST_METHOD"] == "POST" ) 
{
    $write_to_database = 1;
    $munged_password = 0;

    // Clear/Zero-out our variables so they are not null
    $vars = array( "password_1", "password_2", "password_1_err", "password_2_err", "user_name_err", "munged_password", "invite_user_id", "invite_email" );
    set_php_vars( $vars );    
    
    if( $debug == 1 )
    {
        echo "<br>password_1 = " . $_POST['password_1'];
        echo "<br>password_2 = " . $_POST['password_2'];
        echo "<br> write_to_database = " . $write_to_database;
    }
    
    if( empty($_POST["password_1"]))
    {
        $password_1_err = "Password is required";
        $write_to_database = 0;
       
        } else {
        $password_1 = test_input( $_POST["password_1"]);
        // CHECK THAT THE PASSWORD HAS RIGHT STUFF IN IT
        if( !preg_match("/^(?=.*\d)/", $password_1)) // at least 1 digit
        {
            $password_1_err = "<br>at least 1 digit ";
            $munged_password = 1;
        }
        if( !preg_match("/^(?=.*[a-z])/", $password_1)) // at least 1 lowercase letter
        {
            $password_1_err = $password_1_err . "<br>at least 1 lowercase letter ";
            $munged_password = 1;
        }
        if( !preg_match("/^(?=.*[A-Z])/", $password_1)) // at least 1 uppercase letter
        {
            $password_1_err = $password_1_err . "<br>at least 1 uppercase letter ";
            $munged_password = 1;            
        }
        if( !preg_match("/^.{8,32}/", $password_1)) // at least 8-32 chars
        {
            $password_1_err = $password_1_err . "<br>more characters(8-32) ";
            $munged_password = 1;           
        }
        /*****************************
        if( !preg_match("/^(?=.*[@#\-_$%^&+=§!\?])$/", $password_1)) // at least one special char
        {
            $password_1_err = $password_1_err . "at least 8-32 characters ";
            $munged_password = 1;           
        }
        *****************************/
        
        if( $debug == 1 )
            echo "<br>munged_password = " . $munged_password . "<br>";
        
        if( $munged_password == 1 )
        {
            $write_to_database = 0;
            $password_1_err = "<span class=\"orange\">Your password must contain: " . $password_1_err . "</span>";
        }

    }
    if( empty($_POST["password_2"]))
    {
        $password_2_err = "<span class=\"orange\">Verification password is required</span>";
        $write_to_database = 0;
    } else {
        $password_2 = test_input( $_POST["password_2"]);
        // check if name is just letters and whitespace
        if( $password_2 != $password_1 )
        {
            $password_2_err = "<span class=\"orange\">The two passwords do not match</span>";
            $write_to_database = 0;
        }

    }
    if( empty( $_POST["user_name"]))
    {
        $write_to_database = 0;
        $user_name_err = "<span class=orange>You must choose a username</span>";

    } else {
        $user_name = test_input( $_POST["user_name"]);
        // check if username is just letters and whitespace
        if( !preg_match("/^[a-zA-Z0-9 ]*$/",$user_name))
        {
            $user_name_err = "<span class=orange>Use only letters, numbers and spaces</span>";
            $write_to_database = 0;
        }
                
    }
    

    
    // DEFINE "hidden" fields that came from form
    $invite_user_id = $_POST["invite_user_id"];
    $invite_email = $_POST["invite_email"];
}
else // we came here via a link
{
    // check to make sure our invite_user_key is valid AND has not already been used
    $sql_key_check = "SELECT invite_user_id, invite_email, used " 
        . "FROM user_invite "
        . "WHERE invite_user_key=\"" . $_GET["invite_user_key"] . "\"";
    
    if( $debug == 1 ) { echo $sql_key_check . "<br>"; }
    
    $result = $conn->query( $sql_key_check );
            
    $row = $result->fetch_assoc();  
    
    if( $row["invite_user_id"] < 1 )
    {
        exit( "You don't have permission to be here" );
    }
    else if( $row["used"] == 1 )
    {
        exit( "This invitation has already been used" );
    }
    
    
    $invite_user_id = $row["invite_user_id"];
    $invite_email = $row["invite_email"];
    $used = $row["used"];
}

// Write this info to the users table
if( $write_to_database == 1 )
{    
    // hash the password
    $password_encr = password_hash( $password_1, PASSWORD_BCRYPT );
    
    $sql = "INSERT INTO users "
            . "(user_name, password, email_address)"
            . "VALUES(\"" . $user_name . "\",\"" . $password_encr . "\" , \""
            . $invite_email . "\")";

    if( $debug == 1 ) { echo $sql . "<br>"; }

    // check if return result is TRUE
    if( $conn->query( $sql ) === TRUE )
    {         
        // and mark the invitation as used
        $sql_invite_used = "UPDATE user_invite "
                . "SET used=1 "
                . "WHERE invite_user_id=" . $invite_user_id;
        
        if( $debug == 1 ) { echo $sql_invite_used . "<br>"; }
        
        if( $conn->query( $sql_invite_used ) === TRUE )
        {
            // CONGRATULATE OUR NEW USER FOR JOINING RetroChipDB
            echo "Congratulations " . $user_name . " has joined RetroChipDB.<br>";
        } else {
            echo "Error: " . $sql_invite_used . "<br>" . $conn->error;
        } 
        
        // Find our new user_id
        $sql_user_id = "SELECT user_id "
                . "FROM users "
                . "WHERE user_name = \"" . $user_name . "\"";
        
        if( $debug == 1 ) { echo $sql_user_id . "<br>"; }
        
        $result = $conn->query( $sql_user_id );

        $row = $result->fetch_assoc();        

        // ENCOURAGE USER TO ADD MORE USER DATA -> continue to User Account Info section
        $_SESSION["user_id"] = $row["user_id"];
        $_SESSION["first_name"] = "user"; // Set this to something so it is not blank when we see the "account" pull-down menu
        echo "<br>Now that you have successfully created an account<br> ";
        echo "Please add more info to your <b><a href=\"" . $retro_url 
            . "user_account_info.php\">profile</a>.</b>";       
        echo "<p>Or go straight to the fun and create a <a href=\"/login.php\"><span class=orange>new project</class>.</a>";
    }
    else
    {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
                    
}
else
{
?>             
        You have been invited to join RetroChipDB.<br>
        
        <!-- The Info Modal -->
        <div id="myInfoModal" class="modal">

          <!-- Modal content -->
          <div class="modal-content">
            <div class="modal-header">
              <!-- <span class="close">&times;</span> -->
              <span class="close"></span>  
              <h2>About RetroChipDB</h2>
            </div>
            <div class="modal-body">
                The Retro Chip DB exists to help you keep track of the nifty bits you use to
                keep your retro gear running. I developed it for myself and thought that
                there's likely at least one other person who might find this useful. So,
                Trevor, this is for you.<p>
            </div>
          </div>     
        </div>
        <!-- Info Modal Done --> 
        
        <!-- video modal -->
        <div id="myVideoModal" class="modal">

          <!-- Modal content -->
          <div class="modal-video">
            <div class="modal-header">
              <span class="close" id="close3">&times;</span>
              <h2>RetroChipDB</h2>
            </div>
            <div class="modal-body">
                <iframe id="cartoonVideo" width="560" height="315" src="//www.youtube.com/embed/XomSDI_ML2A" frameborder="0" allowfullscreen></iframe> 
            </div>
          </div>     
        </div>              
        <!-- Video modal done -->
        
                <p align="left">
                    <button id="myInfoBtn">What is RetroChipDB</button><button id="myVideoBtn">Watch the Video</button>
                </p>        
        
        <script>
        // Get the modal
        var infoModal = document.getElementById('myInfoModal');

        // Get the button that opens the modal
        var infoBtn = document.getElementById('myInfoBtn');

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName('close')[0];

        // When the user clicks the button, open the modal 
        infoBtn.onclick = function() {
            infoModal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == infoModal) {
                infoModal.style.display = "none";
            }
        }      
        </script>
        <script>
            //get the modal
            var videoModal = document.getElementById('myVideoModal');
                    
            // get the button that opens the modal       
            var videoBtn = document.getElementById("myVideoBtn");
            
            // Get the <span> element that closes the modal
            var span3 = document.getElementById('close3');            
                    
            // when the user clicks the button, open the modal        
            videoBtn.onclick = function() {
                videoModal.style.display = "block";
            };
            // When the user clicks on <span> (x), close the modal
            span3.onclick = function() {
                videoModal.style.display = "none";
            };
            
            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target === videoModal) {
                    videoModal.style.display = "none";
                }
            };
            
        </script> 
<?php
if( $debug == 1 )
{
    echo "<br> write_database = " . $write_to_database;
    echo "<br> invite_user_id = " . $invite_user_id;
    echo "<br> invite_email = " . $invite_email;
    echo "<br> user_name_err = " . $user_name_err;
}

if( empty( $user_name_err ) )
{
    $user_name_err = "Choose your RetroChipDB username";
}
if( empty( $password_1_err ))
{
    $password_1_err = "Choose a password";
}
if( empty( $password_2_err ))
{
    $password_2_err = "Verify password";
}
?>
        
        To sign up, you need to choose a <b>username</b> and a <b>password</b>.<p>
        After you do this, you will have the chance to add more<br>
        info to your account.<br><p>
        <form method="post" name="accept invitation" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <?php echo $user_name_err; ?><br>
            <input type="text" name="user_name" placeholder="Username" onKeyUp="check(this.value)" 
                   size="40" value="<?php echo $user_name; ?>" /><em id="user_name_checker"></em><br>
            <?php echo $password_1_err; ?><br>
            <input type="password" name="password_1" placeholder="Password" 
                   size="40" value="<?php echo $password_1; ?>" /><br>
            <?php echo $password_2_err; ?><br>
            <input type="password" name="password_2" placeholder="Password" 
                   size="40" value="<?php echo $password_2; ?>" /> <br>
            <input type="hidden" name="invite_user_id" value="<?php echo $invite_user_id; ?>">
            <input type="hidden" name="invite_email" value="<?php echo $invite_email; ?>">
            <input type="submit" value="Accept Invitation" />
        </form>
               
<?php
}

$conn->close();

?>
