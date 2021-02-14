<?php
    include 'retro_vars.php';

    $user_name=trim($_GET['user_name']);

    // create connection
    $conn = new mysqli( $servername, $username, $password, $dbname );

    // check connection
    if( $conn->connect_error )
    {
        die( "Connection failed: " . $conn->connect_error );
    }

    if($user_name!="")
    {
        $sql="select user_name from users where user_name=\"" . $user_name . "\"";
        $result = $conn->query( $sql );
        $row = $result->fetch_assoc();
    
        if($row['user_name']==$user_name)
        {
            echo "<b style='color:red;'>\"$user_name\"</b> Is already taken.";
        }
        else
        {
            echo "<b>\"$user_name\"</b> is available.";
        }
    }
?>
