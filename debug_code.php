<?php
/*****************************************
 *  TESTING CODE ($debug=1)
 *****************************************/
if( $debug == 1 )
{
    echo "session id:" . session_id();
    echo "<pre>Session Variables\n";
    var_dump($_SESSION);
    echo"</pre>";
}
    
/////////////////////////////////////////////
?>
