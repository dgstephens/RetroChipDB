<?php

require_once __DIR__.'/vendor/autoload.php';

session_start();
// user_account_info Version .4
// last modified 102918
// modified by: dgs

?>
<link rel="stylesheet" href="user_profile.css">
<?php

$debug=0;
include 'wyf_vars.php';
include 'wyf_functions.php';
include 'debug_code.php';
include_once 'user_class.php';

$activeTab = 0;
if( isset( $_POST['activeTab'] ))
{
    $activeTab = $_POST['activeTab'];
}

?>
<link rel="stylesheet" href="/jquery-ui-1.12.1/jquery-ui.css">

<!-- font-awesome stylesheets -->
<link rel="stylesheet" href="/font-awesome/css/fontawesome.css">
<link rel="stylesheet" href="/font-awesome/css/all.css">
<link rel="stylesheet" href="/font-awesome/css/brands.css">
<link rel="stylesheet" href="/font-awesome/css/regular.css">
<link rel="stylesheet" href="/font-awesome/css/solid.css">
<link rel="stylesheet" href="/font-awesome/css/svg-with-js.css">
<link rel="stylesheet" href="/font-awesome/css/v4-shims.css">

<script src="/jquery-3.3.1.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/jquery-ui-1.12.1/jquery-ui.js"></script>
<script src="gv/manage_records.js"></script>
<?php

// SETUP OUR CONNECTION WITH THE DATABASE
// create connection
$conn = new mysqli( $servername, $username, $password, $dbname );

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

// check to see if we are logged in
// and if not, stop execution
else if( $_SESSION["user_id"] == 0 )
{
    $exit_message = "You are not currently logged in.";
    exit( $exit_message );
}
// IF WE ARE LOGGED IN, continue execition
else
{
    $user_id = $_SESSION["user_id"];
    $client_id = $_SESSION["client_id"];

    // GET OUR CLIENT INFO (url and such)
    $client_info = get_client_info( $conn, $client_id );
    $client_media_banner = get_client_media( $conn, $client_id, "client_media_banner" );
    $client_media_banner_small = get_client_media( $conn, $client_id, "client_media_banner_small" );
    
    // Do we want to show tooltips?
    $show_tooltips = check_show_tooltips( $conn, $user_id );
    
    // Are we a DataLogger subscriber?
    if( $client_info[0]["dataLogger_subscription"])
    {
        $data_logger_id = sprintf("%06d", $user_id );
    }
    
    // TOOLTIPS
    $tooltip_hints = "if checked, you will see hints (also called tooltips) when hovering over certain items";
    
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
    }
    
    if( empty( $_POST["user_l_name"]))
    {
        $user_l_name_err = "Last Name is required";
        $write_to_database = 0;
    } else {
        $user_l_name = test_input( $_POST["user_l_name"]);
    }
    
    if( empty( $_POST["email_address"]))
    {
        $email_address_err = "email address is required";
        $write_to_database = 0;
    } else {
        $email_address = test_input( $_POST["email_address"]);
        // NEED TO CHECK FOR VALID EMAIL ADDRESS
    }
    
    if( empty( $_POST["phone_number_mobile"]))
    {
        $phone_number_mobile = "";
    } else {
        $phone_number_mobile = test_input( $_POST["phone_number_mobile"]);
    }
    
    if( empty( $_POST["tooltips"]))
    {
        $tooltips = "0";
    } else {
        $tooltips = $_POST["tooltips"];
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
    $sql = "SELECT * FROM users WHERE user_id=" . $_SESSION["user_id"];
    
    $result = $conn->query( $sql );
    $row = $result->fetch_assoc();
    
    $user_f_name = $row["user_f_name"];
    $user_l_name = $row["user_l_name"];
    $short_bio = $row["short_bio"];
    $email_address = $row["email_address"];
    $phone_number_mobile = $row["phone_number_mobile"];
    $tooltips = $row["tooltips"];
    $facebook_link = $row["facebook_link"];
    $twitter_link = $row["twitter_link"];
    $instagram_link = $row["instagram_link"];
    $web_url = $row["web_url"];
    $google_access = ($row["google_access_token"] != "");
    
    $user_f_name_err = "";
    $user_l_name_err = "";
    $email_address_err = "";
}


if( $write_to_database == 1 )
{
    $sql = "UPDATE users "
            . "SET user_f_name='" . $user_f_name . "',user_l_name='" . $user_l_name 
            . "',short_bio=\"" . $short_bio 
            . "\",email_address='" . $email_address . "',facebook_link='" . $facebook_link
            . "',twitter_link='" . $twitter_link . "',instagram_link='" .$instagram_link
            . "',web_url='" . $web_url . "',phone_number_mobile='" . $phone_number_mobile
            . "', tooltips=" . $tooltips
            . " WHERE user_id=" . $_SESSION["user_id"];
    
    
    if( $debug == 1 ) { echo $sql . "<br>"; }
    
    $result = $conn->query( $sql );
    
    $_SESSION["user_f_name"] = $user_f_name;
    $_SESSION["user_l_name"] = $user_l_name;
    
}

$g_client = new Google\Client();
$g_client->setAuthConfig('creds/client_secret.json');
$g_client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
$g_client->setRedirectUri('https://whenyoufly.com/google_auth.php');
$g_client->setAccessType('offline');
$g_client->setPrompt('consent');
$auth_url = $g_client->createAuthUrl();

?>
<!DOCTYPE html>
<!-- Copyright 2018 geekpower/aca -->
<html>
    <head>
        <link rel="shortcut icon" type="image/png" href="https://<?php echo $client_info[0]['client_url'] . "/";?>favicon.png" sizes="32x32">
        <link rel="stylesheet" type="text/css" href="acfcstyle.css?<?php echo time(); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, minimal-ui">
        <meta charset="UTF-8">
        <title>WhenYouFly</title>
        <style>
            table, th, td {
                border: 0px solid #36a6cc;
            }
            input[type=text], input[type=tel],
            input[type=date], input[type=time]{
                width: 90%;
            }
            textarea {
                width: 90%;
            }
            
           
        </style>        
    </head>
    <body>
        
<?php        
    ////////////////////////////////////////////
    // MENU BUTTON and HEADER TITLE
    ////////////////////////////////////////////
    include 'pulldown_menu_button.php';
    echo "<div class=\"mainDiv\">"; //start main div
    ?>
    <div class="areaBox">
        <div class="wrap">
            <ul class="tabs group">
                <li><a href="#one" id="one-tab">Profile</a></li>
                <li onclick="drawTab(<?php echo $client_id ?>, <?php echo $user_id ?>, 'pilot')">
                    <a href="#two" id="two-tab">Records</a>
                </li>
                <li onclick="drawTab(<?php echo $client_id ?>, 
                    <?php echo $user_id ?>, 'endorsements')">
                    <a href="#three" id="three-tab">Endorsements</a>
                </li>
            </ul>
            
            <div id="content">
                <div class="tab" id="one">                       
                    <br><b>Update User Account</b><br>
<?php    
                    // if we've just changed our info, let the user know
                    if( $write_to_database == 1 )
                    {
                        echo "<span style='color: orange;'><b>Account Updated</b></span>";
                    }
                    if (!$google_access)
                    {
?>                      <br>
                        <div>
                            Google Calendar Status: Inactive
                        </div>
                        <br>
                        <input type="button" class="updateButton" style="width: 300px"
                            onclick="location.href='<?php echo $auth_url;?>';"
                            value="Enable Google Calendar" />
                        <br>
                        <br>
<?php 
                    }
                    else
                    {
?>                      <br>
                        <div>
                            Google Calendar Status: Active
                        </div>
                        <br>
                        <input type="button" class="updateButton" style="width: 300px"
                            onclick="location.href='google_auth.php?revoke_access=true';"
                            value="Disable Google Calendar" />
                        <br>
                        <br>
<?php               }
?>                  <form method="post" action="user_account_info.php">
                        <table>
                            <tr><td>First Name<br>
                                <input type="text" name="user_f_name" placeholder="First Name" 
                                    value="<?php echo $user_f_name;?>" /> <?php echo $user_f_name_err ?>
                            </td><td>Last Name<br>
                                <input type="text" name="user_l_name" placeholder="Last Name" 
                                    value="<?php echo $user_l_name;?>" /> <?php echo $user_l_name_err ?>
                                </td></tr>
                            <tr><td>Email Address<br>
                                <input type="text" name="email_address" placeholder="email address" 
                                    value="<?php echo $email_address;?>" /> <?php echo $email_address_err ?>
                                </td>
                            </td><td>Mobile Number<br>
                                <input type="tel" name="phone_number_mobile" placeholder="phone number" 
                                    value="<?php echo $phone_number_mobile;?>" />
                                </td></tr>
                            <tr><td>
                                <label class="tooltip container">Show hints?
                                    <input type="checkbox" name="tooltips" value="1" <?php if( $tooltips == 1 ) { echo "checked"; } ?>>
                                    <span class="checkmark"></span>
                                    <?php echo ( $show_tooltips ? "<span class='tooltiptext'>" . $tooltip_hints . "</span>": "" ); ?>
                                </label>
                            </td></tr>
                            <tr><td colspan="2">Short Bio<br>
                                <textarea name="short_bio" placeholder="Short Bio" rows="5"/><?php echo $short_bio;?></textarea>
                                </td></tr>
                            <tr><td>Facebook link<br>
                                <input type="text" name="facebook_link" placeholder="Facebook Link"value="<?php echo $facebook_link;?>" />
                            </td><td>
                                <?php if( $data_logger_id ) { echo "<b>Data Logger ID: " . $data_logger_id . "</b><br>for use with the ACA Data Logger"; } ?>
                            </td></tr>
                            <tr><td>Twitter link<br>
                                <input type="text" name="twitter_link" placeholder="Twitter Link" value="<?php echo $twitter_link;?>" />
                                </td></tr>
                            <tr><td>Instagram link<br>
                                <input type="text" name="instagram_link" placeholder="Instagram Link" value="<?php echo $instagram_link;?>" />
                                </td></tr>
                            <tr><td>Website url<br>
                                <input type="text" name="web_url" placeholder="Your website https://" value="<?php echo $web_url;?>" /> 
                                </td></tr>
                            <tr><td>
                                <input type="submit" class="updateButton" value="Update" />
                                </td></tr>
                        </table>
                    </form>
                </div> <!-- end of tab 1 -->
                <div class="tab" id="two">
                    <div id="pilotRecordList" style="clear: left;"></div>
                </div> <!-- end of tab 2 -->
                <div class="tab" id="three">
                    <div id="pilotEndorsementList" style="clear: left;"></div>
                </div> <!-- end of tab 3 -->
            </div>

            <script>
                $(function(){
                    $("#recordModals").load("gv/manage_records_modals.html");
                    $("#endorsementModals").load("gv/manage_endorsements_modal.html"); 
                });
            </script>

            <div id="recordModals"></div>
            <div id="endorsementModals"></div>

        </div> <!-- end of wrap (tabs) -->
    </div>
        
    <?php
    
    // Show our last login time - which is the penultimate entry in the user_login_track table
    $sql = "SELECT login_time "
            . "FROM user_login_track "
            . "WHERE user_id = " . $_SESSION["user_id"] 
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

$conn->close();

?>

<script>
// This will draw the records and endorsements contents in their tab
$().ready(function() {
    drawTab(<?php echo $client_id; ?>, <?php echo $user_id; ?>, 'pilot');
    drawTab(<?php echo $client_id; ?>, <?php echo $user_id; ?>, 'endorsements');
});

// This runs the tabs functionallity
(function($) {
    var tabs =  $(".tabs li a");

    // Change Tabs on click
    tabs.click(function(e) {
        var content = this.hash;
        // save tab clicked in session
        tabStorage = {loc: 'pilot-profile', tab: content};
        sessionStorage.setItem('lastTab', JSON.stringify(tabStorage));
        // change tab selected (in tab list - not content)
        tabs.removeClass("active");
        $(this).addClass("active");
        // change tab content to tab clicked
        $("#content").find('.tab').hide();
        $(content).fadeIn(200);
        // stop tab from scrolling down
        e.preventDefault();
    });

    // Load last tab open on first load
    tabs.ready(function(e) {                        
        $("#content").find('.tab').hide();
        // remove active class from a tags
        tabs.removeClass("active");
        // get tab saved in session
        var tabHistory = JSON.parse(sessionStorage.getItem('lastTab'));
        if (tabHistory && tabHistory.loc === 'pilot-profile') {
            // add active class to the anchor of saved tab
            $( tabHistory.tab + '-tab').addClass("active");
            // Display data coresponding to saved tab
            $( tabHistory.tab ).fadeIn(200);
        } else {
            $('#one-tab').addClass("active");
            $('#one').fadeIn(200);
        }
    })
})(jQuery);

</script>
