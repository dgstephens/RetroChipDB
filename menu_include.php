<?php
    echo "<div class=\"footerDiv\">\n";
    echo "<div class=\"menuDiv\">\n";
    //echo "<p><b>Menu</b><br>";
    
    if( $debug == 1 ) { echo "basename = [" . basename( $_SERVER['PHP_SELF']) . "]<br>"; }
    
    if( basename( $_SERVER['PHP_SELF']) != "login.php" )
    {
        echo "<a href=\"" . $retro_url . "login.php\">Main</a> &nbsp &nbsp\n";
    }

    if( basename( $_SERVER['PHP_SELF']) != "create_new_mix.php" )
    {
        if( $num_mixes == 0 && basename( $_SERVER['PHP_SELF'] ) == "login.php" ) 
        {
            echo "<a href=\"" . $retro_url . "create_new_mix.php\"><span class=\"orange\">New Project</span></a> &nbsp &nbsp\n";
        }
        else
        {
            echo "<a href=\"" . $retro_url . "create_new_mix.php\">New Mix</a> &nbsp &nbsp\n";
        }
    }
    
    if( basename( $_SERVER['PHP_SELF']) != "support.php" )
    {
        echo "<a href=\"" . $retro_url . "support.php\">Contact/Support</a> &nbsp &nbsp\n";
    }
    
    if( basename( $_SERVER['PHP_SELF']) != "tutorial.php" )
    {
        echo "<a href=\"" . $retro_url . "tutorial.php\">How does this work?</a> &nbsp &nbsp\n";
    }    
    
    if( basename( $_SERVER['PHP_SELF']) != "invite_user.php" )
    {
        echo "<input type=\"button\" onclick=\"location.href='" . $retro_url . "invite_user.php'\" value=\"Invite Friend\"\n";
    }
    echo "</div>\n";
    echo "</div>\n";
?>
