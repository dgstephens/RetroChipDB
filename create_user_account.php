<?php
session_start();
// accept_user_invite Version .4
// last modified 062617
// modified by: dgs
// TODO
//
$debug=0;
include 'myx_vars.php';
include 'myx_functions.php';
include 'debug_code.php';  

// SET VARS
$user_name = $user_name_err = $password_1 = $password_1_err = $password_2 = $password_2_err = "";
        
?>
<!-- Copyright 2017 myxtape -->
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
        <link rel="stylesheet" type="text/css" href="myxstyle.css?<?php echo time(); ?>">
        <meta charset="UTF-8">
        <title>MyxTape Invite User</title>
        <style>
            body { 
                background: none; /* overwrite background color from myxstyle.css */
            }
            html{
                background: url( img/solo_tape_white.png ) fixed no-repeat;
                background-size: cover;
            }
            /* The Modal (background) */
            .modal {
                display: none; /* Hidden by default */
                position: fixed; /* Stay in place */
                z-index: 1; /* Sit on top */
                padding-top: 200px; /* Location of the box */
                left: 0; 
                top: 0;
                width: 100%; /* Full width */
                height: 100%; /* Full height */
                overflow: auto; /* Enable scroll if needed */
                background-color: rgb(0,0,0); /* Fallback color */
                background-color: rgba(0,25,50,0.4); /* Black w/ opacity */
            }

            /* Modal Content */
            .modal-content {
                position: relative;
                background-color: white;
                margin: auto;
                padding: 0;
                border: 1px solid #ea842a; /* this creates a solid border around the box */
                border-radius: 7px;
                width: 40%; /* the width of the box in the browser window */
                box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
                -webkit-animation-name: animatetop;
                -webkit-animation-duration: 0.4s;
                animation-name: animatetop;
                animation-duration: 0.4s
            }
            /* Modal Video Content */
            .modal-video {
                position: relative;
                background-color: white;
                margin: auto;
                padding: 0;
                border: 1px solid #ea842a; /* this creates a solid border around the box */
                border-radius: 7px;
                width: 600px; /* the width of the box in the browser window */
                box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
                -webkit-animation-name: animatetop;
                -webkit-animation-duration: 0.4s;
                animation-name: animatetop;
                animation-duration: 0.4s
            }
            /* Add Animation */
            @-webkit-keyframes animatetop {
                from {top:-300px; opacity:0} 
                to {top:0; opacity:1}
            }

            @keyframes animatetop {
                from {top:-300px; opacity:0}
                to {top:0; opacity:1}
            }

            /* The Close Button */
            .close {
                color: white;
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .close:hover,
            .close:focus {
                color: #000;
                text-decoration: none;
                cursor: pointer;
            }

            .modal-header {
                padding: 2px 16px;
                background-color: #ea842a;
                border-top-left-radius: 7px;
                border-top-right-radius: 7px;
                color: white;
            }

            .modal-body {
                padding: 2px 16px;
                background-color: white;
            }

            .modal-footer {
                padding: 2px 16px;
                background-color: #ea842a;
                border-bottom-left-radius: 7px;
                border-bottom-right-radius: 7px;
                color: white;
            }
            
            /* The Login Button */
            #myBtn {
                background-color: #ff7800;
                border: none;
                color: white;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }
            
            #myInfoBtn {
                background-color: #ffffff;
                border: 1px solid #888;                
                color: black;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }
           #myVideoBtn {
                background-color: #ffffff;
                border: 1px solid #888;                
                color: black;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }            
        </style>
    </head>
    <body>

<?php

include 'debug_code.php';    

echo "<H1><b>MyxTape</h1>\n<P>Create a MyxTape account</P></b>\n";


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
	echo "<br>email_address = " . $POST['email_address'];
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
    if( empty( $_POST["email_address"]))
    {
        $write_to_database = 0;
        $email_address_err = "<span class=orange>You must enter an email address</span>"; 
                
    } else {
        $email_address = test_input( $_POST["email_address"]);
        // BETTER CHECK FOR VALID EMAIL ADDRESS
    }    

    
    // DEFINE "hidden" fields that came from form
    $invite_user_id = $_POST["invite_user_id"];
}

// Write this info to the myx_user table
if( $write_to_database == 1 )
{    
    // hash the password
    $password_encr = password_hash( $password_1, PASSWORD_BCRYPT );
    
    $sql = "INSERT INTO myx_user "
            . "(user_name, password, email_address)"
            . "VALUES(\"" . $user_name . "\",\"" . $password_encr . "\" , \""
            . $email_address . "\")";

    if( $debug == 1 ) { echo $sql . "<br>"; }

    // check if return result is TRUE
    if( $conn->query( $sql ) === TRUE )
    {         
        // CONGRATULATE OUR NEW USER FOR JOINING MyxTape
        echo "Congratulations " . $user_name . " has joined MyxTape.<br>";
        
        // Find our new user_id
        $sql_user_id = "SELECT user_id "
                . "FROM myx_user "
                . "WHERE user_name = \"" . $user_name . "\"";
        
        if( $debug == 1 ) { echo $sql_user_id . "<br>"; }
        
        $result = $conn->query( $sql_user_id );

        $row = $result->fetch_assoc();        

        // ENCOURAGE USER TO ADD MORE USER DATA -> continue to User Account Info section
        $_SESSION["myx_user_id"] = $row["user_id"];
        $_SESSION["myx_user_f_name"] = "user"; // Set this to something so it is not blank when we see the "account" pull-down menu
        echo "<br>Now that you have successfully created an account<br> ";
        echo "Please add more info to your <b><a href=\"" . $myx_url 
            . "user_account_info.php\">profile</a>.</b>";       
        echo "<p>Or go straight to the fun and create a <a href=\"/login.php\"><span class=orange>new mix</class>.</a>";
    }
    else
    {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
                    
}
else
{
?>             
        <!-- The Info Modal -->
        <div id="myInfoModal" class="modal">

          <!-- Modal content -->
          <div class="modal-content">
            <div class="modal-header">
              <span class="close" id="close2">&times;</span>
              <h2>About Myxtape</h2>
            </div>
            <div class="modal-body">
                MyxTape is all about creating wonderful music mixes for yourself and your friends.
                It is a simple platform that allows you to upload music you already own to create
                a custom mix on an analog audio cassette. We then mail that cassette, along with
                custom art, to you or a friend.<p>
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
              <h2>MyxTape</h2>
            </div>
            <div class="modal-body">
                <iframe id="cartoonVideo" width="560" height="315" src="//www.youtube.com/embed/XomSDI_ML2A" frameborder="0" allowfullscreen></iframe> 
            </div>
          </div>     
        </div>              
        <!-- Video modal done -->
        
                <p align="left">
                    <button id="myInfoBtn">What is MyxTape</button><button id="myVideoBtn">Watch the Video</button>
                </p>        
        
        <script>
        // Get the modal
        var infoModal = document.getElementById('myInfoModal');

        // Get the button that opens the modal
        var infoBtn = document.getElementById('myInfoBtn');

        // Get the <span> element that closes the modal
        var span = document.getElementById('close2');

        // When the user clicks the button, open the modal 
        infoBtn.onclick = function() {
            infoModal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            infoModal.style.display = "none";
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
    $user_name_err = "Choose your MyxTape username";
}
if( empty( $email_address_err ) )
{
    $email_address_err = "What is your email address?";
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
        <form method="post" name="create_account"  action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <?php echo $user_name_err; ?><br>
            <input type="text" name="user_name" placeholder="Username" onKeyUp="check(this.value)" 
                   size="40" value="<?php echo $user_name; ?>" /><em id="user_name_checker"></em><br>
            <?php echo $email_address_err; ?><br>
            <input type="text" name="email_address" placeholder="email address" 
                   size="40" value="<?php echo $email_address; ?>" /><br>
            <?php echo $password_1_err; ?><br>
            <input type="password" name="password_1" placeholder="1 Capital letter, 1 number, 8 characters" 
                   size="40" value="<?php echo $password_1; ?>" /><br>
            <?php echo $password_2_err; ?><br>
            <input type="password" name="password_2" placeholder="Password" 
                   size="40" value="<?php echo $password_2; ?>" /> <br>
            <input type="hidden" name="invite_user_id" value="<?php echo $invite_user_id; ?>">
            <input type="hidden" name="invite_email" value="<?php echo $invite_email; ?>">
            <input type="submit" value="Create Account" />
        </form>
               
<?php
}

$conn->close();

?>
