<?php
session_start();
// user_account_info Version .3
// last modified 062317
// modified by: dgs
// TODO
$debug=0;
include 'myx_vars.php';
include 'myx_functions.php';
include 'debug_code.php';
?>
<!DOCTYPE html>
<!-- Copyright 2017 MyxTape -->
<html>
    <head>
        <link REL="icon" HREF="favicon.ico">
        <link rel="stylesheet" type="text/css" href="myxstyle.css?<?php echo time(); ?>">
        <meta charset="UTF-8">
        <title>MyxTape</title>
        <style>
            <?php include 'myx_pulldown_menu_style.php'; ?>
            html{

            }
            table {
                /* border-collapse: collapse; */
            }

            table, th, td {
                border: 0px solid #36a6cc;
            }

            th, td {
                padding: 2px;
                /* border-bottom: 1px solid #ddd; *//* just a horizontal line on the bottom */
            }

            td {
                text-align: left;
            }

            th {
                height: 50px;
                text-align: left;
                background-color: #71b9d2;
                color: white; /* text color */
            }
        </style>        
    </head>
    <body>

<?php



// check to see if we are logged in
if( $_SESSION["myx_user_id"] == 0 )
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


// define variables and set to empty values


if( $_SERVER["REQUEST_METHOD"] == "POST" ) // we came here from submitting this form
{
    $write_to_database = 1;

    if( empty($_POST["user_f_name"]))
    {
        $user_f_name_err = "First Name is required";
        $write_to_database = 0;
    } else {
        $user_f_name = test_input( $_POST["user_f_name"]);
        // check if name is just letters and whitespace
        if( !preg_match("/^[a-zA-Z ]*$/",$user_f_name))
        {
            $user_f_name_err = "Use only letters and spaces";
            $write_to_database = 0;
        }
    }
    
    if( empty( $_POST["user_l_name"]))
    {
        $user_l_name_err = "Last Name is required";
        $write_to_database = 0;
    } else {
        $user_l_name = test_input( $_POST["user_l_name"]);
        // check if name is just letters and whitespace
        if( !preg_match("/^[a-zA-Z ]*$/",$user_f_name))
        {
            $user_l_name_err = "Use only letters and spaces";
            $write_to_database = 0;
        }
    }
    
    if( empty( $_POST["email_address"]))
    {
        $email_address_err = "email address is required";
        $write_to_database = 0;
    } else {
        $email_address = test_input( $_POST["email_address"]);
        // NEED TO CHECK FOR VALID EMAIL ADDRESS
    }
    
    if( empty( $_POST["short_bio"]))
    {
        $short_bio = "";
    } else {
        $short_bio = test_input( $_POST["short_bio"]);
    }
    
    if( empty( $_POST["facebook_link"]))
    {
        $facebook_link = "";
    } else {
        $facebook_link = test_input( $_POST["facebook_link"] );
    }

    if( empty( $_POST["twitter_link"]))
    {
        $twitter_link = "";
    } else {
        $twitter_link = test_input( $_POST["twitter_link"] );
    }    
    
    if( empty( $_POST["instagram_link"]))
    {
        $instagram_link = "";
    } else {
        $instagram_link = test_input( $_POST["instagram_link"] );
    }

    if( empty( $_POST["web_url"]))
    {
        $web_url = "";
    } else {
        $web_url = test_input( $_POST["web_url"] );
    }
}
else // we came here from a link
{    
    // GET ACCOUNT INFO
    $sql = "SELECT * FROM myx_user WHERE user_id=" . $_SESSION["myx_user_id"];
    
    $result = $conn->query( $sql );
    $row = $result->fetch_assoc();
    
    $user_f_name = $row["user_f_name"];
    $user_l_name = $row["user_l_name"];
    $short_bio = $row["short_bio"];
    $email_address = $row["email_address"];
    $facebook_link = $row["facebook_link"];
    $twitter_link = $row["twitter_link"];
    $instagram_link = $row["instagram_link"];
    $web_url = $row["web_url"];
    
    $user_f_name_err = "";
    $user_l_name_err = "";
    $email_address_err = "";
}


if( $write_to_database == 1 )
{
    $sql = "UPDATE myx_user "
            . "SET user_f_name='" . $user_f_name . "',user_l_name='" . $user_l_name 
            . "',short_bio='" . $short_bio 
            . "',email_address='" . $email_address . "',facebook_link='" . $facebook_link
            . "',twitter_link='" . $twitter_link . "',instagram_link='" .$instagram_link
            . "',web_url='" . $web_url 
            . "' WHERE user_id=" . $_SESSION["myx_user_id"];
    
    if( $debug == 1 ) { echo $sql . "<br>"; }
    
    $result = $conn->query( $sql );
    
    $_SESSION["myx_user_f_name"] = $user_f_name;
    $_SESSION["myx_user_l_name"] = $user_l_name;
    
    echo "User " . $user_f_name . " " . $user_l_name . " updated<br>";
    echo "Return to <a href=\"" . $myx_url . "login.php\"><b>main</b></a> "
            . "or <a href=\"" . $myx_url . "user_account_info.php\"><b>View</b></a> Account Info"; 
    
}
else // we've come here from a link - display our current data
{
    ////////////////////////////////////////////
    // MENU BUTTON and HEADER TITLE
    ////////////////////////////////////////////
    include 'myx_pulldown_menu_button.php';
    echo "<div class=\"mainDiv\">"; //start main div
    
    echo "<p><b>Update User Account</b></p>\n";
    ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <table>
                    <tr><td>First Name<br>
                        <input type="text" name="user_f_name" placeholder="First Name" size="40" 
                            value="<?php echo $user_f_name;?>" /> <?php echo $user_f_name_err ?>
                    </td><td>Last Name<br>
                        <input type="text" name="user_l_name" placeholder="Last Name" size="40" 
                            value="<?php echo $user_l_name;?>" /> <?php echo $user_l_name_err ?>
                        </td></tr>
                    <tr><td>Email Address<br>
                        <input type="text" name="email_address" placeholder="email address" size="40" 
                               value="<?php echo $email_address;?>" /> <?php echo $email_address_err ?>
                        </td></tr>
                    <tr><td colspan="2">Short Bio<br>
                        <textarea name="short_bio" placeholder="Short Bio" rows="5" cols="80"/><?php echo $short_bio;?></textarea>
                        </td></tr>
                    <tr><td>Facebook link<br>
                        <input type="text" name="facebook_link" placeholder="Facebook Link" size="40" value="<?php echo $facebook_link;?>" />
                        </td></tr>
                    <tr><td>Twitter link<br>
                        <input type="text" name="twitter_link" placeholder="Twitter Link" size="40" value="<?php echo $twitter_link;?>" />
                        </td></tr>
                    <tr><td>Instagram link<br>
                        <input type="text" name="instagram_link" placeholder="Instagram Link" size="40" value="<?php echo $instagram_link;?>" />
                        </td></tr>
                    <tr><td>Website url<br>
                        <input type="text" name="web_url" placeholder="Your website http://" size="40" value="<?php echo $web_url;?>" /> 
                        </td></tr>
                    <tr><td>
                        <input type="submit" value="Update" />
                        </td></tr>
                </table>
            </form>
    <?php
    
    // Show our last login time - which is the penultimate entry in the myx_user_login_track table
    $sql = "SELECT login_time "
            . "FROM myx_user_login_track "
            . "WHERE user_id = " . $_SESSION["myx_user_id"] 
            . " ORDER BY user_login_track_id DESC LIMIT 1";
    
    if( $debug == 1 ) { echo $sql . "<br>"; }
    
    $result = $conn->query( $sql );
    $row = $result->fetch_assoc();
    echo "<div style=\"text-align: right; font-size: small; color: #aaaaaa\">";
    echo "Last login on " . $row["login_time"] . "<br>";
    echo "</div>";
    
    // end main Div
    echo "</div>";
    // MENU
    include 'menu_include.php';

}

$conn->close();

?>

