<?php
// copyright 2019 StarBird Technologies, LLC
// version 1.14 updated 070319 by dgs
session_start();

$debug=0;

include 'wyf_vars.php';
include 'wyf_functions.php';

use WhenYouFly\Scheduler\Event;
use WhenYouFly\Scheduler\User;
use WhenYouFly\Scheduler\Client;

if( $debug == 1 )
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// TOOLTIPS
$tooltip_charter_flight = "Is this a charter flight and thus may require a co-pilot?";
$tooltip_fly_with_instructor = "Do you want to schedule a flight with your instructor?";
$tooltip_fly_with_student = "Do you want to fly with your student?";
$tooltip_instructional_flight = "Is this an instructional flight with a CFI/CFII and a student?";
$tooltip_add_a_student = "Would you like to add a student to your flight?";

// Define/set variables
$instructor_not_exist = 0;
$pilot_not_exist = 0;
$copilot_not_exist = 0;
$student_not_exist = 0;
$object_id = 0;
$from_time = 0;
$from_time_err = "";
$to_time_err = "";

// create connection
$conn = new mysqli( $servername, $username, $password, $dbname );

// check connection
if( $conn->connect_error )
{
    die( "Connection failed: " . $conn->connect_error );
}

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
}

// instantiate classes
$clientObject = new Client( $conn, $client_id );

// get some client class data
$default_event_duration = $clientObject->getDefaultEventDuration( );
$event_start_time = $clientObject->getDefaultEventStartTime();
$event_end_time = $clientObject->getDefaultEventEndTime();

$checkAircraftAirworthiness = $clientObject->getCheckAircraftAirworthiness();
$checkPilotAirworthiness    = $clientObject->getCheckPilotAirworthiness();

// GET POST OR GET VARIABLES
// entry_id
if( isset( $_POST['entry_id'] ) )
{
    $entry_id = $_POST['entry_id'];
    $entryObject = new Event( $conn, $entry_id );
}
else if( isset( $_GET['entry_id'] ) )
{
    $entry_id = $_GET['entry_id'];
    $entryObject = new Event( $conn, $entry_id );
}
if( $debug == 1 )
{
    echo "<br>entry_id: " . $entry_id;
}

// start_date
// start_date is for our mobile default/week/day views
// it ensures we return to the date-rage/week/day that we
// came from
if( isset( $_POST['start_date'] ) )
    $start_date = $_POST['start_date'];
else if( isset( $_GET['start_date']))
    $start_date = $_GET['start_date'];
if( $debug == 1 )
    echo "<br>start_date: " . $start_date; 

 // date
if( isset( $_POST['date']))
    $date = $_POST['date'];
else if( isset( $_GET['date']))
    $date = $_GET['date'];
if( $debug == 1 )
    echo "<br>date: " . $date;

// object id - can come via CHART view
if( isset( $_POST['object_id']))
    $object_id = $_POST['object_id'];
else if( isset( $_GET['object_id']))
    $object_id = $_GET['object_id'];
if( $debug == 1 )
    echo "<br>object_id: " . $object_id;

// from_time - can come via CHART view
if( isset( $_POST['from_time']))
{
    $from_time = $_POST['from_time'];
    $event_start_time = $from_time;
    $event_end_time = date('H:i:s', strtotime($event_start_time)+$default_event_duration*60);
}
else if( isset( $_GET['from_time']))
{
    $from_time = $_GET['from_time'];
    $event_start_time = $from_time;
    $event_end_time = date('H:i:s', strtotime($event_start_time)+$default_event_duration*60);
}

if( $debug == 1 )
{
    echo "<br>from_time: " . $from_time;
}

// Default flight type - single pilot
$flight_types = array( array('flight_type_id' => '1', 'flight_type_name' => 'Single pilot' ));

// Do we want to show tooltips?
$show_tooltips = check_show_tooltips( $conn, $user_id );  

// Do we have an instructor?
$has_instructor = check_for_instructor( $conn, $user_id );

// Admin or administrator status
$administrator = check_for_administrator( $conn, $user_id );

if( $administrator == 1 )
    $all_pilots = getAllUsers( $conn, $client_id, "all_pilots" );

// provides instruction
$provides_instruction = check_provides_instruction( $conn, $client_id );

if( $provides_instruction )
{
    $instructors = getAllUsers( $conn, $client_id, "instructors" );
    array_push( $flight_types, array( 'flight_type_id' => '2', 'flight_type_name' => 'Instructional'));
}

// Charter Operation
$charter_op = check_for_charter( $conn, $client_id );

if( $charter_op )
{
    $charter_pilots = getAllUsers( $conn, $client_id, "charter_pilots" );
    $co_pilots = getAllUsers( $conn, $client_id, "co_pilots" );
    array_push( $flight_types, array( 'flight_type_id' => '3', 'flight_type_name' => 'Charter'));
}    

// can schedule maintenance and client is a maintenance module subscriber
if( $administrator )
{
    array_push( $flight_types, array(
        'flight_type_id'=>4,
        'flight_type_name'=>'Maintenance'));
}

// CHECK FOR CFI-ness
$cfi_flag = check_for_cfi( $conn, $user_id );

if( $cfi_flag == 1 )
   $cfi_students = check_cfi_students( $conn, $user_id ); 

$all_students = getAllUsers( $conn, $client_id, "all_students" );


// 8888888888 Y88b   d88P 8888888 .d8888b. 88888888888 8888888 888b    888  .d8888b.       8888888888 888b    888 88888888888 8888888b. Y88b   d88P 
// 888         Y88b d88P    888  d88P  Y88b    888       888   8888b   888 d88P  Y88b      888        8888b   888     888     888   Y88b Y88b d88P  
// 888          Y88o88P     888  Y88b.         888       888   88888b  888 888    888      888        88888b  888     888     888    888  Y88o88P   
// 8888888       Y888P      888   "Y888b.      888       888   888Y88b 888 888             8888888    888Y88b 888     888     888   d88P   Y888P    
// 888           d888b      888      "Y88b.    888       888   888 Y88b888 888  88888      888        888 Y88b888     888     8888888P"     888     
// 888          d88888b     888        "888    888       888   888  Y88888 888    888      888        888  Y88888     888     888 T88b      888     
// 888         d88P Y88b    888  Y88b  d88P    888       888   888   Y8888 Y88b  d88P      888        888   Y8888     888     888  T88b     888     
// 8888888888 d88P   Y88b 8888888 "Y8888P"     888     8888888 888    Y888  "Y8888P88      8888888888 888    Y888     888     888   T88b    888                                                                                                                                                                                                                                                                                                 
if( $entry_id > 0 )
{
    if( $debug == 1 )
        echo "<br>Existing Entry";    
    
    // get calendar objects for which the user has schedule-access
    $sql = 'SELECT calendar_objects.object_id AS object_id, object_name'
		. ' FROM calendar_objects LEFT JOIN'
		. '     (SELECT *'
		. '     FROM user_item_restrictions'
		. '     WHERE user_id=?) AS user_item_restrictions'
		. ' ON calendar_objects.object_id=user_item_restrictions.object_id'
		. ' WHERE client_id=? AND visible=1'
		. '     AND user_item_restrictions.object_id IS NULL'
        . ' ORDER BY object_name';

    if( $debug )
    {
        echo '<br>SQL prepared statement: ' . $sql;
        echo '<br>$user_id=' . $user_id;
        echo '<br>$client_id=' . $client_id;
    }
    
    $stmt = $conn->prepare( $sql );
    $stmt->bind_param( 'ii', $user_id, $client_id );
    $stmt->execute();

    //$result = $conn->query( $sql );
    $result = $stmt->get_result();
    
    for( $calObjectSet = array (); $row = $result->fetch_assoc(); $calObjectSet[] = $row );
      
    // GET OUR ENTRY INFORMATION
    $sql = 'SELECT calendar.detail, calendar.from_date, calendar.to_date,'
        . '     calendar.from_time, calendar.to_time, calendar.object_id,'
        . '     calendar.subject, calendar.user_id, calendar.confirmed,'
        . '     calendar.pilot_2, calendar.scheduled_by,'
        . '     calendar.charter_flight, calendar.instructor_id,'
        . '     calendar.deleted, calendar.delete_reason,'
        . '     calendar.is_maintenance,'
        . '     IFNULL(users.user_name, users2.user_name) AS user_name,'
        . '     IFNULL(users.user_f_name, users2.user_f_name) AS user_f_name,'
        . '     IFNULL(users.user_l_name, users2.user_l_name) AS user_l_name,'
        . '     calendar_objects.object_name'
        . ' FROM calendar'
        . '     LEFT JOIN users ON calendar.user_id=users.user_id'
        . '     JOIN calendar_objects ON calendar.object_id=calendar_objects.object_id'
        . '     JOIN (SELECT * FROM users) AS users2 ON calendar.scheduled_by=users2.user_id'
        . ' WHERE calendar.entry_id = ' . $entry_id;

    if( $debug == 1 )
        echo "<br>" . $sql;    
    
    $result = $conn->query( $sql );

    $row = $result->fetch_assoc();
    
    // assign some variables
    $deleted = $row['deleted'];
    $delete_reason = stripslashes( $row['delete_reason'] );
    
    // Set my_cfi to 0
    $my_cfi = 0;
      
    // If I am a CFI, who are my students and am I looking at one of their entries?
    if( $cfi_flag == 1 )
    {
        // Get a list of all our students (student_id, user_f_name, user_l_name )
        $cfi_students = check_cfi_students ( $conn, $user_id);
          
        // Is this CFI also this user's CFI?
        foreach( $cfi_students as &$value )
        {
            if( $value["student_id"] == $row['user_id'] )
                $my_cfi = 1;
        }
    }
    
    // If this entry is for a student flying with a CFI and I am an administrator
    if( $administrator && $row['instructor_id'] > 0 )
    {
        $cfi_students = check_cfi_students( $conn, $row['instructor_id'] );
    }
    
    // pull some data from the SQL request above
    $charter_flight = $row['charter_flight'];
    $date_stuff = explode( '-', $row['from_date']);
    $month_num = $date_stuff[1];
    
    
    // If this entry isn't confirmed, let any user know.
    if( $row['confirmed'] == 0 )
    {
        echo "<span style='font-size: small; color: orange;'>This entry is not confirmed</span><br>";
        
        // IF we are a CFI, we can confirm it.
        if( $cfi_flag == 1 )
        {
            echo "<a href='./calendar.php?entry_id=" . $entry_id . "&confirmed_by=" . $user_id . "&month_num=" 
                    . $month_num . "'><span style='font-size: small; color: orange;'>Confirm this entry?</span></a><br>";
        }
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // if we created this entry OR we are this student's CFI (AND it's not a charter flight)
    // or we are an administrator then we can EDIT/DELETE it
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    if( ( $user_id == $row['user_id'] && !$charter_flight) || ( $my_cfi && !$charter_flight )|| $administrator )
    {
        if( $debug == 1 )
        {
            echo "<br>start_date: " . $start_date;
        }
        
        // if we are not the user for whom this entry was created, but we are their CFI or an ADMIN
        if( ( $my_cfi == 1 || $_SESSION["admin_user"] > 0 ) && $user_id != $row['user_id'] )
        {
            echo "Editing " . $row['user_f_name'] . " " . $row['user_l_name'] . "'s entry<br>";
        }
?>
      

            <script>             
                $(document).ready(function(){               
                    $('input.timepicker').timepicker({
                        timeFormat: 'h:mm p',
                        interval: 30,
                        minTime: '12:00am',
                        maxTime: '11:59pm',
                        dynamic: false,
                        dropdown: true,
                        scrollbar: true,
                        scrollDefault: 'now',
                    }); 

                    $( '#toTime' ).timepicker( 'setTime', '<?php echo $row['to_time'];?>' );

                    $( '#fromTime').timepicker('setTime', '<?php echo $row['from_time'];?>') 

                }); 

            </script>                
            <!-- Delete Reason Div -->
            <div class="deleteDialogue" id="deleteDialogue" >
                <form name="deleteEntry" method="post" action="calendar.php" >
                    Reason for deleting this entry?
                    <br>
                    <input type="text" name="deleteReason" style="width: 80%;">
                    <br>
                    <input type="button" class="addButton" onclick="hideDeleteReasonDialogueFunction()" value="CANCEL">
                    <!-- <input type="button" class="delButton" onclick="location.href='calendar.php?deleteEntry=1&entry_id=<?php echo $entry_id; ?>&start_date=<?php echo $start_date; ?>'; " value="DELETE"> -->
                    <input type="hidden" name="entry_id" value="<?php echo $entry_id; ?>">
                    <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                    <input type="hidden" name="deleteEntry" value="1">
                    <input type="submit" class="delButton" id="delete_submit" value="DELETE" >
                </form>
            </div>
            
            <form name="editForm" id="editForm" autocomplete="off" method="post" action="calendar.php">
                <table id="addEvent">
                    <tr><td style='border: none;' colspan='1'>Schedule<br>
                            <select style="font-size: small;" name="calendar_object" id="calendar_object" onchange="validate();">
<?php
        // CHOOSE A CALENDAR OBJECT
        foreach( $calObjectSet as &$value)
        {
            if( $value['object_id'] == $row['object_id'])
                echo "<option value=" . $value['object_id'] . " selected>" . $value['object_name'] . "</option>\n";
            else
                echo "<option value=" . $value['object_id'] . ">" . $value['object_name'] . "</option>\n";            
        }
?>
                            </select>
                        </td>
                        <td style='border: none;'>
                        
<?php
        // FIRST ROW, RIGHT COLUMN - These are all exclusive of each other
        // BUT WHAT IF WE ARE AN ADMIN AND A STUDENT PILOT?
        // if we are a pilot with an instructor, and this is our flight, show the
        // [ ] FLY WITH INSTRUCTOR
        if( $has_instructor && $user_id == $row['user_id'] && $administrator == 0 )
        {
            // [ ] fly with instructor
?>
                        <label class="tooltip container" style="margin: 12px 0px;">Fly with my instructor
                            <input id="flyWithMyInstructorCheckBox" type="checkbox" name="fly_with_instructor" value="1" 
                                <?php if( $row['instructor_id'] > 0 && $user_id == $row['user_id']) { echo "checked"; } ?> onClick="flyWithInstructorHideStudents()">
                            <span class="checkmark"></span>
                            <?php //echo ( $show_tooltips ? "<span class='tooltiptext'>" . $tooltip_fly_with_instructor . "</span>": "" ); ?>
                        </label>                       
                            
<?php                            
        }
         // if we are an administrator show the Available PILOTS menu's
        // CHARTER FLIGHT
        if( $administrator && $row['charter_flight'] == 1)
        {
?>                   
                        <span class="mediumNotice" >This is a charter flight </span>    
<?php
        }
        // or we are an instructional flight add a table row to provide for instructor and students
        // INSTRUCTIONAL FLIGHT
        if( $administrator && $row['instructor_id'] > 0 )
        {
?>                    
                        <span class="mediumNotice" >This is an instructional flight </span>   
<?php   
        }
        // or we are an instructor and this is our flight and we'd like to add a student
        // [ ] ADD A STUDENT
        if( $cfi_flag && $row['user_id'] == $user_id && $administrator == 0 )
        {
?>                    
                        <label class="tooltip container" style="margin: 12px 0px;">Add a student?
                            <input id="addAStudentCheckbox" type="checkbox" name="add_student" value="1" onclick='addAStudent()'>   
                            <span class="checkmark"></span>
                            <?php // echo ( $show_tooltips ? "<span class='tooltiptext'>" . $tooltip_add_a_student . "</span>": "" ); ?>
                        </label>   
<?php    
        }
        // CLOSE FIRST ROW
?>            
                        </td>
                    </tr>
                    <tr>
<?php
        // SECOND ROW ITEMS
        // LEFT COLUMN
        // CHARTER PILOTS
        if( $administrator && $row['charter_flight'] && !$row['is_maintenance'] )
        {
?>
                        <td style='border: none;' colspan="1">
                            <span id="userOfInterest">Pilot</span><br>
                            <select style="font-size: small;" name="pilot_id">
                                <option value=0>Pilots</option>
<?php                
            foreach( $charter_pilots as &$value )
            {
                 echo "<option value=" . $value['user_id'] . " " . ($value['user_id'] == $row['user_id'] ? "selected" : $pilot_not_exist++ ) . ">" 
                    . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";       
            }
?>
                            </select>
<?php
            // If this pilot no longer exists or does not have this role
            if( $pilot_not_exist == sizeof( $charter_pilots ) )
            {
                echo "<br><span class='smallNotice'>" . $row['user_f_name'] . " " . $row['user_l_name'] . " is no longer a charter pilot</span>";
            }
?>

                        </td>
<?php                            
        } 
        // INSTRUCTORS
        else if( $administrator && $row['instructor_id'] > 0 && !$row['is_maintenance'] )
        {
?>
                        <td style='border: none;' colspan="1">Instructor<br>
                            <select id="instructorPilots" style="font-size: small;" name="instructor_id" onchange="validate();">
                                <option value=0>Instuctors</option>
<?php                
            foreach( $instructors as &$value )
            {
                echo "<option value=" . $value['user_id'] . " " . ($value['user_id'] == $row['instructor_id'] ? "selected" : $instructor_not_exist++ ) . ">" 
                    . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n"; 
            }
?>
                            </select>
<?php
            // If this instructor no longer exists or does not have this role
            if( $instructor_not_exist == sizeof( $instructors ) )
            {
                // first get this instructor's info
                $thisInstructor = getThisUser( $conn, $row['instructor_id']);

                echo "<br><span class='smallNotice'>" . $thisInstructor[0]['user_f_name'] . " " . $thisInstructor[0]['user_l_name'] . " is not an instructor</span>";
            }
    ?>                            
                        </td>
<?php                            
        }    
        
        // if We are an administrator and this is a single pilot flight
        // ALL PILOTS
        else if( $administrator && !$row['is_maintenance'] )
        {
?>
                        <td style='border: none;' colspan="1">Available Pilots<br>
                            <select style="font-size: small;" name="pilot_id">
                                <option value=0>Pilots</option>
<?php                
            foreach( $all_pilots as &$value )
            {
                 echo "<option value=" . $value['user_id'] . " " . ($value['user_id'] == $row['user_id'] ? "selected" : $pilot_not_exist++ ) . ">" 
                    . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";       
            }
?>
                            </select>
<?php
            // If this pilot no longer exists or does not have this role
            if( $pilot_not_exist == sizeof( $all_pilots ) )
            {            
                echo "<br><span class='smallNotice'>" . $row['user_f_name'] . " " . $row['user_l_name'] . " is not a pilot</span>";
            }
?>                            
                        </td>
<?php                            
        }     
        // if we are a CFI AND this is NOT our own entry AND this is NOT a charter flight show the CFI student's menu
        // STUDENTS
        else if( $cfi_flag == 1 && $row['user_id'] != $user_id && !$row['is_maintenance'] )
        {
?>            
                        <td style='border: none;' colspan="1">
                            <div id="selectStudentDropdown" >
                                Student<br>
                                <select id="selectStudent" style="font-size: small;" editable="true" name="student_id[]" onchange="selectStudentFunction();">
                                    <option value=0>No Student</option>
<?php                
            foreach( $cfi_students as &$value )
            {
                echo "<option value=" . $value['student_id'] . " " . ($value['student_id'] == $row['user_id'] ? "selected" : $student_not_exist++ ) . ">" 
                        . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }
        
 ?>                   
                                </select>
<?php
            // If this student no longer exists or does not have this role
            if( $student_not_exist == sizeof( $cfi_students ) )
            { 
                echo "<br><span class='smallNotice'>" . $row['user_f_name'] . " " . $row['user_l_name'] . " is not your student</span>";
            }
?>                            
                            </div>  
                        </td>
<?php
        }
        // if we are a CFI AND this IS our own entry AND this is NOT a charter flight show the CFI student's menu
        // Only show this if the CFI selects "add a student"
        // STUDENTS
        else if( $cfi_flag == 1 && $row['user_id'] == $user_id && !$row['is_maintenance'] )
        {
?>            
                        <td style='border: none;' colspan="1">
                            <div id="selectStudentDropdown" style="display: none;">
                                Student<br>
                                <select id="selectStudent" style="font-size: small;" editable="true" name="student_id[]" onchange="selectStudentFunction();">
                                    <option value=0>No Student</option>
<?php                
            foreach( $cfi_students as &$value )
            {
                echo "<option value=" . $value['student_id'] . " " . ($value['student_id'] == $row['user_id'] ? "selected" : "") . ">" 
                        . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }
        
 ?>                   
                                </select>
                            </div>
                        </td>
<?php
        }        
        // SECOND ROW
        // RIGHT COLUMN
        // admin && charter_op && instruction
        // If we are a charter operator and this is a charter flight,
        // then let us see this extra data entry field for co-pilot
        // CO-PILOT
        if( $administrator && $charter_flight && !$row['is_maintenance'] )
        {
?>
                        <td style='border: none;' colspan="1">Co-Pilot<br>
                            <select style="font-size: small;" name="pilot_2">
                                <option value=0>Co-Pilots</option>
<?php                
            foreach( $co_pilots as &$value )
            {
                echo "<option value=" . $value['user_id'] . " " . ($value['user_id'] == $row['pilot_2'] ? "selected" : $copilot_not_exist++ ) . ">" 
                        . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }  
?>
                            </select>
<?php
        // If this CoPilot no longer exists or does not have this role
        if( $copilot_not_exist == sizeof( $co_pilots ) && !$row['is_maintenance'] )
        {
            // first get this instructor's info
            $thisCoPilot = getThisUser( $conn, $row['pilot_2']);
            
            if( $thisCoPilot == 0 )
            {
                echo "<br><span class='smallNotice'>No Co-Pilot selected</span>";
            }
            else
            { 
                echo "<br><span class='smallNotice'>" . $thisCoPilot[0]['user_f_name'] . " " . $thisCoPilot[0]['user_l_name'] . " is not a Co-Pilot</span>";
            }
        }
?>                         
                        </td>
<?php                               
        }
        // if we an administrator and this is a student flight with instructor
        // STUDENT
        else if( $administrator && $row['instructor_id'] > 0 && !$row['is_maintenance'] )
        {
?>            
                        <td  style='border: none;' colspan="1">
                            <span id="studentPilots" style="display: block;">Student<br>
                                <select id="selectThisStudentPilot" style="font-size: small;" name="student_id[]" onchange="selectAllStudentsFunction(this);">
                                    <option value=0>Students</option>
<?php                
            foreach( $cfi_students as &$value )
            {
                echo "<option value=" . $value['student_id'] . " " . ( $value['student_id'] == $row['user_id']  ? "selected" : $student_not_exist++ ) . ">" 
                        . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }  
            
            // Add the All Students option
            if( $administrator )
            {
                echo "<option value=all>Show All</option>\n";
            }            
?>
                                </select>
<?php                            
            // If this student no longer exists or is not this instructor's student
            if( $student_not_exist == sizeof( $cfi_students ) )
            {
                // first get this instructor's info
                $thisInstructor = getThisUser( $conn, $row['instructor_id']);

                if( $thisInstructor == 0 )
                {
                    echo "<br><span class='smallNotice'>Invalid Instructor</span>";
                }
                else
                { 
                    echo "<br><span class='smallNotice'>" . $row['user_f_name'] . " " . $row['user_l_name'] . " is not " . $thisInstructor[0]['user_f_name'] . "'s student</span>";
                }
            }    
?>                                  
                            </span>
                            <span id="allStudentPilots" style="display: none;">Student<br>
                                <select id="selectAllStudentPilots" style="font-size: small;" name="student_id[]" onchange="selectThisStudentFunction()">
                                    <option value=0>All Students</option>
<?php                
            foreach( $all_students as &$value )
            {
                echo "<option value=" . $value['user_id'] . ">" . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }
            if( $administrator )
            {
                echo "<option value=only>instructor's only</option>\n";
            }
        
?>        
                                </select>
                            </span>                                
      
                        </td>
<?php                                
            
        }
        // if we are an instructor AND this is a student's flight
        // [ ]FLY WITH STUDENT?
        else if( $cfi_flag && $row['user_id'] != $user_id && $administrator == 0 && !$row['is_maintenance'] )
        {
?>            
                        <td style="border: none;" colspan="1">
                            <label id="flyWithStudentCheckbox" class="tooltip container" style="margin: 12px 0px; display: block;" >Fly with my student
                                <input id="flyWithStudentCheckboxInput" type="checkbox" name="fly_with_student" value="1" <?php  echo ( $row['instructor_id'] > 0 ? "checked" : "" ) ?>>
                                <span class="checkmark"></span>
                                <?php // echo ( $show_tooltips ? "<span class='tooltiptext'>" . $tooltip_fly_with_student . "</span>": "" ); ?>
                            </label>                              
                        </td>
<?php                        
        }
        // if we are an instructor AND this is our flight
        // [ ] FLY WITH STUDENT?
        else if( $cfi_flag && $row['user_id'] == $user_id && !$row['is_maintenance'] )
        {
?>            
                        <td style="border: none;" colspan="1">
                            <span id="flyWithStudentCheckbox" style="display: none;">
                                <label class="tooltip container" style="margin: 12px 0px;" >Fly with my student
                                    <input id="flyWithStudentCheckboxInput" type="checkbox" name="fly_with_student" value="1">
                                    <span class="checkmark"></span>
                                    <?php // echo ( $show_tooltips ? "<span class='tooltiptext'>" . $tooltip_fly_with_student . "</span>": "" ); ?>
                                </label>         
                            </span>
                        </td>
<?php                        
        }

        else if( $row['is_maintenance'] )
        {
            echo "<td style='border: none;'><br>This is a maintenance entry.<p></td>";
        }

        else
        {
            // just a blank table cell
            echo "<td style='border: none;'></td>";
        }
?>                                
                    </tr>
                    <tr><td style='border: none;'>From Date<br>                     
                        <script>                   
                            $( function() {
                                $( "#fromDatepicker" ).datepicker();
                                $( "#fromDatepicker" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
                                $( "#fromDatepicker" ).datepicker( "setDate", "<?php echo $row['from_date']; ?>" );
                            });

                        </script>
                        <input type="text" id="fromDatepicker" class="datepicker" autocomplete="off" name="from_date" >
                        
                    </td><td style='border: none;'>From Time<br>

                        <input id="fromTime" class="timepicker" name="from_time"/> <?php echo $from_time_err ?>  
                      
                    </td></tr>
                    <tr><td style='border: none;'>To Date<br>
                         <script>                   
                            $( function() {
                               $( "#toDatepicker" ).datepicker();
                               $( "#toDatepicker" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
                               $( "#toDatepicker" ).datepicker( "setDate", "<?php echo $row['to_date']; ?>" );
                            });
                        </script>
                        <input type="text" id="toDatepicker" class="datepicker" autocomplete="off" name="to_date" >
                        
                    </td><td style='border: none;'>To Time<br>

                        <input id="toTime" class="timepicker" name="to_time" data-default-time="<?php echo $row['to_time'];?>" data-interval="30"/> <?php echo $to_time_err ?>
                        
                    </td></tr>
                    <tr><td style='border: none;' colspan="2">Subject<br>
                        <input type="text" name="subject" value="<?php echo $row['subject']; ?>" style="width:90%;" />
                    </td></tr>
                    <tr><td style='border: none;' colspan="2">Notes<br>
                        <textarea name="detail" placeholder="Details" <?php echo ( $deleted == 0 ? "rows=2" : "rows=1" ); ?> style="width:90%;"><?php echo $row['detail'];?> </textarea>
                    </td></tr>
<?php 
    if( $deleted == 1 )
    {
?>
                    <tr><td style='border: none;' colspan="2">Delete Reason<br>
                         <input type="text" name="delete_reason" value="<?php echo $delete_reason; ?>" style="width: 90%;" >
                        </td><tr>
<?php
    }                        
?>
                    <tr><td style='border: none;' colspan="2">
                        <input type="hidden" name="is_maintenance"
                            value="<?php echo $row['is_maintenance']; ?>">
                        <input type="hidden" name="editEntry" value="1">
                        <input type="hidden" name="entry_id" value="<?php echo $entry_id; ?>">
                        <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                        <input type="submit" value="UPDATE" class="addButton" id="update_submit"/>
<?php
    if( $deleted == 0 )
    {
?>        

                        <input type="button" class="delButton" value="DELETE" onclick="showDeleteReasonDialogueFunction()" >
<?php
    }
?>    
                        </td></tr>
                </table>
            </form>

<?php
    } 
    else 
    ////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////
    // JUST SHOW THE FACTS AND DO NOT ALLOW EDITING
    ////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////        
    {
        if( $entryObject->getPrimaryPilotInfo()['scheduled_by'] != $entryObject->getPrimaryPilotInfo()['user_id'] )
        {
            echo "<b>" . $entryObject->getScheduledBy()['user_f_name'] . " " . $entryObject->getScheduledBy()['user_l_name'] . "</b><br>";
        }
        else
        {
            echo "<b>" . $entryObject->getPrimaryPilotInfo()['user_f_name'] . " " . $entryObject->getPrimaryPilotInfo()['user_l_name'] . "</b><br>";
        }
        echo "has scheduled <b>" . $entryObject->getObjectInfo()['object_name'] . "</b><br>";
        
        // Flight Type
        echo "Flight Type: <b>" . $entryObject->getFlightType() ."</b><br>";

        if( $row['scheduled_by'] != $row['user_id'] )
        {
            echo "Pilot: <b>" . $entryObject->getPrimaryPilotInfo()['user_f_name'] . " " . $entryObject->getPrimaryPilotInfo()['user_l_name'] . "</b><br>";
        }
          
        if( $entryObject->getCoPilot() != 0 )
        {
            echo "Co-Pilot: <b>" . $entryObject->getCoPilot()['user_f_name'] . " " . $entryObject->getCoPilot()['user_l_name'] . "</b><br>";
        }
        
        if( $entryObject->getThisFlightInstructor() != 0 )
        {
            echo "Instructor: <b>" . $entryObject->getThisFlightInstructor()['user_f_name'] . " " . $entryObject->getThisFlightInstructor()['user_l_name'] . "</b><br>";
        }
        
        echo "Subject: " . $entryObject->getEventData()['subject'] . "<br>";        
        echo "From: " . $entryObject->getEventData()['from_date'] . " " . $entryObject->getEventData()['from_time'] . "<br>";
        echo "To: " . $entryObject->getEventData()['to_date'] . " " . $entryObject->getEventData()['to_time'] . "<br>";
        echo "Detail: " . $entryObject->getEventData()['detail'] . "<br>";
?>        
        <input type="button" class="addButton" onclick="location.href='calendar.php?notifyOnDelete=1&entry_id=<?php echo $entry_id; ?>&start_date=<?php echo $start_date; ?>'; " value="ALERT">
        me if this entry is deleted.
<?php        
    }

}

// 888b    888 8888888888 888       888      8888888888 888b    888 88888888888 8888888b. Y88b   d88P 
// 8888b   888 888        888   o   888      888        8888b   888     888     888   Y88b Y88b d88P  
// 88888b  888 888        888  d8b  888      888        88888b  888     888     888    888  Y88o88P   
// 888Y88b 888 8888888    888 d888b 888      8888888    888Y88b 888     888     888   d88P   Y888P    
// 888 Y88b888 888        888d88888b888      888        888 Y88b888     888     8888888P"     888     
// 888  Y88888 888        88888P Y88888      888        888  Y88888     888     888 T88b      888     
// 888   Y8888 888        8888P   Y8888      888        888   Y8888     888     888  T88b     888     
// 888    Y888 8888888888 888P     Y888      8888888888 888    Y888     888     888   T88b    888
else if( $entry_id == 0 ) //this is a new record
{
    if( $debug == 1 )
        echo "<br>New Entry";
    
    $date_stuff = explode( '-', $date );
    
    $month_num = $date_stuff[1];

    // get calendar objects for which the user has schedule-access
    $sql = 'SELECT calendar_objects.object_id AS object_id, object_name'
		. ' FROM calendar_objects LEFT JOIN'
		. '     (SELECT *'
		. '     FROM user_item_restrictions'
		. '     WHERE user_id=?) AS user_item_restrictions'
		. ' ON calendar_objects.object_id=user_item_restrictions.object_id'
		. ' WHERE client_id=? AND visible=1'
		. '     AND user_item_restrictions.object_id IS NULL'
        . ' ORDER BY object_name';

    if( $debug )
    {
        echo '<br>SQL prepared statement: ' . $sql;
        echo '<br>$user_id=' . $user_id;
        echo '<br>$client_id=' . $client_id;
    }

	$stmt = $conn->prepare( $sql );
    $stmt->bind_param( 'ii', $user_id, $client_id );
    $stmt->execute();

    $result = $stmt->get_result();

    for( $calObjectSet = array (); $row = $result->fetch_assoc(); $calObjectSet[] = $row );

    // DOES THIS PILOT NEED CONFIRMATION TO ADD AN ENTRY?
    $sql = "SELECT confirm_cal_entry FROM users "
                . "WHERE user_id = " . $user_id;
    
    $result = $conn->query( $sql );
    
    if( $debug == 1 )
        echo "<br>" . $sql;
    
    $confirm_cal_entry = $result->fetch_row();
   
    // SET UP OUR FORM
    if( $confirm_cal_entry[0] == 1 )
        echo "<span style='color: orange; font-size: small;'>This entry will require confirmation by your instructor or an admin.</span>";
    // echo "<br><b>Date</b>\n" . $date;
    
    
    
    /*************************
     * NEW ENTRY
     *************************/
    ?>
        <script>             
            $(document).ready(function(){               
                $('input.timepicker').timepicker({
                    timeFormat: 'h:mm p',
                    interval: 30,
                    minTime: '12:00am',
                    maxTime: '11:59pm',
                    dynamic: false,
                    dropdown: true,
                    scrollbar: true,
                    scrollDefault: 'now' 
                }); 
                
                $( '#toTime2' ).timepicker( 'setTime', '<?php echo $event_end_time;?>' );
                
                $( '#fromTime2')
                    .timepicker('setTime', '<?php echo $event_start_time;?>' ) 
            
                    .timepicker('option', 'change', function(time) {
                        // update startTime option in timepicker-2
                        var diff = <?php echo $default_event_duration; ?>;
                        var newDateObj = new Date( time.getTime() + diff*60000 );
                        $('#toTime2').timepicker('setTime', newDateObj);
                    });

            }); 
          
        </script>

        <form name="newForm" id="newForm" autocomplete="off" method="post" action="calendar.php">
            <table id="addEvent">
                <tr><td style='border: none;' colspan="1">Schedule<br>

                    <select style="font-size: small;" name="calendar_object" id="calendar_object" onchange="validate()">
                        <option value="0">Schedule</option>
<?php   
        // CHOOSE A CALENDAR OBJECT
        foreach( $calObjectSet as &$value)
        {
            if( $value['object_id'] == $object_id )
                echo "<option value=" . $value['object_id'] . " selected>" . $value['object_name'] . "</option>\n";
            else
                echo "<option value=" . $value['object_id'] . ">" . $value['object_name'] . "</option>\n";            
        }
?>
                    </select>
                </td>
                <td style="border: none; vertical-align: bottom;">
 <?php
        // FIRST ROW, RIGHT COLUMN
        // if we are a pilot with an instructor, show the
        // [ ] fly with instructor checkbox
        if( $has_instructor && !$administrator )
        {
            // [ ] fly with instructor
?>
                    <label class="tooltip container" style="margin: 12px 0px;">Fly with my instructor
                        <input id="flyWithMyInstructorCheckBox" type="checkbox" name="fly_with_instructor" value="1" onClick="flyWithInstructorHideStudents()">
                        <span class="checkmark"></span>
                        <?php // echo ( $show_tooltips ? "<span class='tooltiptext'>" . $tooltip_fly_with_instructor . "</span>": "" ); ?>
                    </label>                       
                            
<?php                            
        }
        // if we are an administrator and this is a charter op or we provide instruction
        // what type of flight do we want to create?
        if( $administrator )
        {
?>                                        
                        Type of Flight<br>
                        <select id="flight_type" style="font-size: small; display:block;" name="flight_type" onchange="flightTypeFunction(); validate();">
<?php                
            foreach( $flight_types as &$value )
            {
                echo "<option value=" . $value['flight_type_id'] . ">" . $value['flight_type_name'] . "</option>\n";          
            }
?>
                        </select>
          
<?php   
        }             
        // CLOSE FIRST ROW
?>       
                </td>
            </tr>
            <tr>  
<?php
        // SECOND ROW ITEMS
        // LEFT COLUMN
        // ADMIN
        // [ ] Pilots
        if( $administrator )
        {
?>                           
                        <td style='border: none;' colspan="1"><span id="userOfInterest">
                            <?php echo ( $charter_op ? "Pilot" : "Available Pilots" ); ?></span><br>
                            <select id="allPilots" style="font-size: small; display:block;" name="pilot_id" onchange="validate();">
                                <option value=0>Pilots</option>
<?php                
            foreach( $all_pilots as &$value )
            {
                echo "<option value=" . $value['user_id'] . ">" . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }
?>
                            </select>
                            <select id="charterPilots" style="font-size: small; display: none;" name="pilot_1">
                                <option value=0>Charter Pilots</option>
<?php                
            foreach( $charter_pilots as &$value )
            {
                echo "<option value=" . $value['user_id'] . ">" . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }
?>
                            </select>
                            <select id="instructorPilots" style="font-size: small; display: none;" name="instructor_id" onchange="validate();">
                                <option value=0>Instructors</option>
<?php                
            foreach( $instructors as &$value )
            {
                echo "<option value=" . $value['user_id'] . ">" . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }
?>
                            </select> 
                            
                            
                            
                        </td>
<?php                                
            
        }       
        // CFI -> PRESENT THIS CFI'S STUDENTS
        // Students [ ] Fly with student?
        else if( $cfi_flag == 1 )
        {
?>            
                        <td style='border: none;' colspan="1">
                            <div id="selectStudentDropdown" >Student<br>
                                <select id="selectStudent" style="font-size: small;" editable="true" name="student_id[]" onchange="selectStudentFunction();">
                                    <option value=0>No Student</option>
<?php                
            foreach( $cfi_students as &$value )
            {
                echo "<option value=" . $value['student_id'] . ">" . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }
        
 ?>                   
                                </select>
                            </div>
                        </td>
                        <td style="border: none;" colspan="1">                            
                            <label class="tooltip container" id="flyWithStudentCheckbox" style="margin: 12px 0px; display: none;" >Fly with my student
                                <input id="flyWithStudentCheckboxInput" type="checkbox" name="fly_with_student" value="1">
                                <span class="checkmark"></span>
                                <?php // echo ( $show_tooltips ? "<span class='tooltiptext'>" . $tooltip_fly_with_student . "</span>": "" ); ?>
                            </label>  
                        </td>                          
<?php
        }
        // SECOND ROW
        // RIGHT COLUMN
        // admin && $charter_op || $instruction
        // COPILOTS OR STUDENTS
        if( $administrator )
        {
            // allow for second pilot
?>
                        <td style='border: none;' colspan="1">
                            <span id="charterCoPilots" style="display: none;">Co-Pilot<br>
                                <select id="selectCharterCoPilots" style="font-size: small;" name="pilot_2">
                                    <option value=0>Co-Pilots</option>
<?php                
            foreach( $co_pilots as &$value )
            {
                echo "<option value=" . $value['user_id'] . ">" . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }                               
        
?>        
                                </select>
                            </span>
                            <span id="studentPilots" style="display: none;">Student<br>
                                <!-- Dynamically created select statement goes here 
                                     Everything between the span tags will be removed -->
                                <select id="selectThisStudentPilot" style="font-size: small;" name="student_id[]">
                                    <option value=0>Students</option>                                
                                </select>
                            </span>     
                            <span id="allStudentPilots" style="display: none;">Student<br>
                                <select id="selectAllStudentPilots" style="font-size: small;" name="student_id[]" onchange="selectThisStudentFunction()">
                                    <option value=0>All Students</option>
<?php                
            foreach( $all_students as &$value )
            {
                echo "<option value=" . $value['user_id'] . ">" . $value['user_f_name'] . " " . $value['user_l_name'] . "</option>\n";          
            }
            if( $administrator )
            {
                echo "<option value=only>instructor's only</option>\n";
            }
        
?>        
                                </select>
                            </span>                             
                        </td>
<?php
        }
?>
                    </tr>
                    <tr><td style='border: none;'>From Date<br>
                        <script>                   
                            $( function() {
                                $( "#fromDatepicker2" ).datepicker();
                                $( "#fromDatepicker2" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
                                $( "#fromDatepicker2" ).datepicker( "setDate", "<?php echo $date; ?>" );
                            });
                        </script>
                        <input type="text" id="fromDatepicker2" class="datepicker" autocomplete="off" name="from_date" placeholder="yyyy-mm-dd">
                    </td><td style='border: none;'>From Time<br>
                        <input id='fromTime2' class="timepicker" name="from_time"/> <?php echo $from_time_err ?>
                    </td></tr>
                    <tr><td style='border: none;'>To Date<br>
                         <script>                   
                            $( function() {
                                $( "#toDatepicker2" ).datepicker();
                                $( "#toDatepicker2" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
                                $( "#toDatepicker2" ).datepicker( "setDate", "<?php echo $date; ?>" );
                            });
                        </script>
                        <input type="text" id="toDatepicker2" class="datepicker" autocomplete="off" name="to_date" placeholder="yyyy-mm-dd">
                    </td><td style='border: none;'>To Time<br>
                        <input id='toTime2' class="timepicker" name="to_time"/> <?php echo $to_time_err ?>
                    </td></tr>
                    <tr><td style='border: none;' colspan="2">Subject<br>
                        <input type="text" name="subject" placeholder="Subject" style="width:90%" />
                    </td></tr>
                    <tr><td style='border: none;' colspan="2">Notes<br>
                        <textarea name="detail" placeholder="Notes" rows="2" style="width:90%"/></textarea>
                    </td></tr>
                    <tr><td style='border: none;' colspan="2">
                        <input type="hidden" name="addEntry" value="1">
                        <input type="hidden" name="entry_id" value="-1">
                        <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                        <input type="hidden" name="confirm_cal_entry" value="<?php echo $confirm_cal_entry[0]; ?>">
                        <input type="hidden" name="month_num" value="<?php echo $month_num; ?>">
                        <input type="submit" style="font-weight: bold;" value="Add" class="addButton" id="submit" disabled="disabled" />
                        </td>
                    </tr>
                </table>
            </form>

<div id="airworthiness-warnings">
    <ul style="list-style-type: disk;">
        <li id="warning-airw-error" style="display: none;">
            <span style="color: red;">
                <b>Error checking airworthiness. Please try reloading.</b>
            </span>
        </li>
        <li id="warning-flight-limit" style="display: none;">
            <span style="color: red;">
                This pilot has reached their <b>flight limit</b>
            </span>
        </li>
        <li id="warning-airw-generic" style="display: none;">
            <span style="color: red;">
                <b>Unable to schedule. Please contact your admin</b>
            </span>
        </li>
        <li id="warning-aircraft-reg" style="display: none;">
            <span style="color: red;">
                No valid <b>registration</b> for selected plane
            </span>
        </li>
        <li id="warning-aircraft-airw-cert" style="display: none;">
            <span style="color: red;">
                No valid <b>airworthiness certificate</b> for selected plane
            </span>
        </li>
        <li id="warning-pilot-med-cert" style="display: none;">
            <span style="color: red;">
                No valid <b>medical certificate</b> for selected pilot
            </span>
        </li>
        <li id="warning-instructor-med-cert" style="display: none;">
            <span style="color: red;">
                No valid <b>medical certificate</b> for selected instructor
            </span>
        </li>
        <li id="warning-student-solo-endorsement" style="display: none;">
            <span style="color: red;">
                No valid <b>solo endorsement</b> for selected student
            </span>
        </li>
    </ul>
</div>

<?php
}
?>
        <script>
            
            function flightTypeFunction()
            {
                var flightTypeSelect = document.getElementById( "flight_type" );

                // get user of interest (pilot/mechanic)
                var userOfInterest = document.getElementById("userOfInterest");
                console.log('user of interest: ' + userOfInterest.innerHTML);

                    // get the all pilots select box
                var allPilots = document.getElementById("allPilots");
                
                // get the charter pilots select box
                var charterPilots = document.getElementById("charterPilots");
                
                // get the co pilots select box
                var charterCoPilots = document.getElementById("charterCoPilots"); 
                var selectCharterCoPilots = document.getElementById( "selectCharterCoPilots" );

                // get the charter pilots select box
                var instructorPilots = document.getElementById("instructorPilots");
                
                // get the student select box
                var studentPilots = document.getElementById("studentPilots");  
                var selectThisStudentPilot = document.getElementById( "selectThisStudentPilot" );
                var selectAllStudentPilots = document.getElementById( "selectAllStudentPilots" );
                
                // allPilots.style.display = flightTypeSelect.value == "1" ? "block" : "none";
                if( flightTypeSelect.value == "1" )
                {
                    allPilots.style.display = "block";
                    document.getElementById("allPilots").value =
                        <?php echo $user_id; ?>;
                }
                else
                {
                    allPilots.style.display = "none";
                    allPilots.value = "0";
                }
                
                // charterPilots.style.display = flightTypeSelect.value == "3" ? "block" : "none";
                // charterCoPilots.style.display = flightTypeSelect.value == "3" ? "block" : "none"; 
                if( flightTypeSelect.value == "3" )
                {
                    charterPilots.style.display = "block";
                    charterCoPilots.style.display = "block";
                }
                else
                {
                    charterPilots.value = "0";
                    selectCharterCoPilots.value = "0";
                    charterPilots.style.display = "none";
                    charterCoPilots.style.display = "none";
                    
                }

                // instructorPilots.style.display = flightTypeSelect.value == "2" ? "block" : "none";
                // studentPilots.style.display = flightTypeSelect.value == "2" ? "block" : "none";
                if( flightTypeSelect.value == "2" )
                {
                    instructorPilots.style.display = "block";
                    studentPilots.style.display = "block";
                }
                else
                {
                    
                    selectThisStudentPilot.value = "0";
                    selectAllStudentPilots.value = "0";
                    instructorPilots.value = "0";
                    instructorPilots.style.display = "none";
                    studentPilots.style.display = "none";
                }

                // TODO: draw a dropdown for selecting a mechanic
                if( flightTypeSelect.value == "4" )
                {
                    userOfInterest.innerHTML = "";
                }
                      
            }
 
            function addAStudent()
            {
                // first get the checkbox
                var addCheckBox = document.getElementById("addAStudentCheckbox");

                // get the students select box
                var addSelect = document.getElementById("selectStudentDropdown");
                
                var addFlyWithStudentCheckbox = document.getElementById("flyWithStudentCheckbox");
                
                var flyWithMyInstructorCheckBox = document.getElementById( "flyWithMyInstructorCheckBox" );

                if( addCheckBox.checked == true )
                {
                    addSelect.style.display = "block";
                    flyWithMyInstructorCheckBox.checked = false;
                } else {     
                    addSelect.style.display = "none";
                    addFlyWithStudentCheckbox.checked = false;
                    addFlyWithStudentCheckbox.style.display = "none";
                }  
            }             

            function selectStudentFunction()
            {
                // first get the checkbox
                var studentCheckBox = document.getElementById("flyWithStudentCheckbox");
                var studentCheckBoxInput = document.getElementById( "flyWithStudentCheckboxInput" );
                var selectStudent = document.getElementById( "selectStudent" );

                
                if( selectStudent.value == "0" )
                {
                    studentCheckBox.style.display = "none";
                    studentCheckBoxInput.checked = false;
                } else {
                    studentCheckBox.style.display = "block";
                }
            }
            
            function selectAllStudentsFunction(el)
            {
                // get the AllStudentPilots 
                var allStudentPilots = document.getElementById("allStudentPilots");

                // get the selectStudent select box
                var studentPilots = document.getElementById("studentPilots");
                
                var selectThisStudentPilot = document.getElementById("selectThisStudentPilot");
                
                var selectAllStudentPilots = document.getElementById("selectAllStudentPilots");

                // show All Students
                studentPilots.style.display = selectThisStudentPilot.value == "all" ? "none" : "block";
                allStudentPilots.style.display = selectThisStudentPilot.value == "all" ? "block" : "none";
                
                if (el.value == 0)
                {
                    document.getElementById( "update_submit" ).disabled = true;
                }
                else
                {
                    document.getElementById( "update_submit" ).disabled = false;
                }
                
                /* document.getElementById("flyWithStudentCheckbox").required = (studentCheckBox.style.display == "block" ); */
            }    
            
            function selectThisStudentFunction()
            {
                // get the AllStudentPilots 
                var allStudentPilots = document.getElementById("allStudentPilots");

                // get the selectStudent select box
                var studentPilots = document.getElementById("studentPilots");
                
                var selectThisStudentPilot = document.getElementById("selectThisStudentPilot");
                
                var selectAllStudentPilots = document.getElementById("selectAllStudentPilots");
                
                // show Only This Instructor's students
                studentPilots.style.display = selectAllStudentPilots.value == "only" ? "block" : "none";
                allStudentPilots.style.display = selectAllStudentPilots.value == "only" ? "none" : "block";
                
                /* document.getElementById("flyWithStudentCheckbox").required = (studentCheckBox.style.display == "block" ); */
            }   
            
            function flyWithInstructorHideStudents()
            {
                var flyWithMyInstructorCheckbox = document.getElementById( "flyWithMyInstructorCheckBox" );
                var selectStudentDropdown = document.getElementById( "selectStudentDropdown" );
                var selectStudent = document.getElementById( "selectStudent" );
                var flyWithStudentCheckbox = document.getElementById( "flyWithStudentCheckbox" );
                var flyWithStudentCheckboxInput = document.getElementById( "flyWithStudentCheckboxInput");
                var addAStudentCheckbox = document.getElementById( "addAStudentCheckbox" );
                
                if( flyWithMyInstructorCheckbox.checked == true )
                {
                    selectStudent.value = "0";
                    flyWithStudentCheckboxInput.checked = false;
                    if( addAStudentCheckbox )
                    {
                        addAStudentCheckbox.checked = false;
                    }
                    selectStudentDropdown.style.display = "none";
                    flyWithStudentCheckbox.style.display = "none";
                    
                    
                } else {
                    selectStudentDropdown.style.display = "block";
                }                                
            }
            
            function showDeleteReasonDialogueFunction()
            {
                var deleteDialogueDiv = document.getElementById( "deleteDialogue" );
                
                deleteDialogueDiv.style.display = "block";
            }
            
            function hideDeleteReasonDialogueFunction()
            {
                var deleteDialogueDiv = document.getElementById( "deleteDialogue" );
                
                deleteDialogueDiv.style.display = "none";
            }            
            
            
           
        </script>
<?php
// ONLY force 1 Checkbox if we are an admin AND this is a charter org
// This is primarily for when chosing between an instructional flight
// OR a charter flight
if( $administrator && $charter_op )
{
?>
        <!-- Only allow one checkbox to be selected at a time -->
        <script>
            $(document).ready(function(){
                $('input:checkbox').click(function() {
                    $('input:checkbox').not(this).prop('checked', false);
                });
            });
        </script>
<?php        
}
?>
        <!-- Dynamic mysql query for student pilots -->
        <script type="text/javascript">
            $(function() {
              $("#instructorPilots").change(function() {
                var value = $('#instructorPilots').val();
                var value2 = <?php echo $administrator; ?>;
                 $.post('db_cfi_student_query.php',{value:value,value2:value2}, function(data){
                   $("#studentPilots").html(data);
                 });
                 return false;
              });
            });
        </script>

<script>

/**
 * Validate a schedule item according to a few criteria:
 *   object ID > 0
 *   if client has check_aircraft_airworthiness set:
 *     - aircraft must have valid registration
 *     - aircraft must have valid airworthiness certificate
 *   if client has check_pilot_airworthiness set:
 *     - pilot must have valid medical certificate unless flying with an
 *       instructor (in which case the instructor must have a valid medical cert
 *     - student soloists must have valid valid solo endorsement
 */
function validate() {

    // enable submit by default
    $("#submit").attr("disabled",false);
    $("#update_submit").attr("disabled",false);

    // hide all warnings by default
    document.getElementById("warning-airw-generic").style.display = 'none';
    document.getElementById("warning-airw-error").style.display = 'none';
    document.getElementById("warning-airw-generic").style.display = 'none';
    document.getElementById("warning-aircraft-reg").style.display = 'none';
    document.getElementById("warning-aircraft-airw-cert")
        .style.display = 'none';
    document.getElementById("warning-pilot-med-cert").style.display = 'none';
    document.getElementById("warning-instructor-med-cert")
        .style.display = 'none';
    document.getElementById("warning-student-solo-endorsement")
        .style.display = 'none';
    document.getElementById("warning-flight-limit")
        .style.display = 'none';

    // get object ID
    let e = document.getElementById('calendar_object');
    let objectID = e.options[e.selectedIndex].value;

    // object must be selected
    if(objectID <= 0)
    {
        $("#submit").attr("disabled", true);
        $("#update_submit").attr("disabled", true);
    }

    // check client settings for airworthiness checking
    let checkAircraftAirworthiness = parseInt( <?php
        echo $checkAircraftAirworthiness?> );
    let checkPilotAirworthiness = parseInt( <?php
        echo $checkPilotAirworthiness?> );

    // get flight (or entry) type
    let flightType = document.getElementById("flight_type").value;

    // if maintenance entry, skip airworthiness checking altogether
    if(flightType == '4')
    {
        return;
    }

    // get pilot ID
    let pilotSelect = document.getElementById("allPilots");
    let pilotID = pilotSelect.value;

    if(pilotID <= 0)
    {
        // select the current user as the pilot by default
        pilotSelect.value = <?php echo $user_id; ?>;
        pilotID = <?php echo $user_id; ?>;
    }

    // Is this flight in the past of present/future?
    currentDate = Date.now();

    fromDateElement = document.getElementById("fromDatepicker")
        || document.getElementById("fromDatepicker2");
    fromTimeElement = document.getElementById("fromTime2")
        || document.getElementById("fromTime2");
    fromDate = fromDateElement.value;
    fromTime = fromTimeElement.value;

    scheduledDate = new Date(fromDate + ' ' + fromTime).getTime();

    pastFlight = scheduledDate < Date.now();

    // check user has not already hit their flight limit
    if(!pastFlight) {
        underFlightLimit(pilotID).then(function(response) {
            if(response['status'] === 'good' && response['under'] == false)
            {
                document.getElementById("warning-flight-limit").style
                    .display = 'list-item';
                $("#submit").attr("disabled", true);
                $("#update_submit").attr("disabled", true);
            }
        })
        .catch(function(response){
            console.log("AJAX request failed");
            document.getElementById("warning-airw-generic")
                .style.display = 'list-item';
            $("#submit").attr("disabled", true);
            $("#update_submit").attr("disabled", true);
        });
    }

    // get schedule date
    let to_date_el = document.getElementById('toDatepicker')
        || document.getElementById('toDatepicker2');
    let expirationDate = to_date_el.value;

    if(objectID > 0 && checkAircraftAirworthiness)
    {
        checkIfAircraft(objectID)
        .then(function(response){
            if(response['status'] === 'good'
                && response['is_aircraft'] === true) {

                checkAircraftRegistration(objectID, expirationDate);
                checkAircraftAirworthinessCert(objectID, expirationDate);
            }
        })
        .catch(function(response){
            console.log("request failed");
            document.getElementById("warning-airw-generic")
                .style.display = 'list-item';
        });
    }

    if(checkPilotAirworthiness)
    {
        // is this an instructional flight?
        //let flightType = document.getElementById("flight_type").value;
        if(flightType == '2')
        {
            let instructorID =
                document.getElementById("instructorPilots").value;

            checkMedicalCertificate(instructorID, expirationDate)
            .then(function(response){
                let validMedCert = response['valid'];
                let warning =
                    document.getElementById("warning-instructor-med-cert");
                if(!validMedCert) {
                    console.log("<?php echo $user_id; ?>");
                    checkIsStudent(<?php echo $user_id; ?>)
                    .then(function(response){
                        let userIsStudent = response['isStudent'];
                        if (userIsStudent === false) {
                            document.getElementById("warning-instructor-med-cert")
                                .style.display = 'list-item';
                        } else {
                            document.getElementById("warning-airw-generic")
                                .style.display = 'list-item';
                        }
                        $("#submit").attr("disabled", true);
                        $("#update_submit").attr("disabled", true);
                    })
                    .catch(function(response){
                        console.log("request failed");
                        document.getElementById("warning-airw-generic")
                            .style.display = 'list-item';
                    });
                }
            })
            .catch(function(response){
                console.log("request failed");
                document.getElementById("warning-airw-generic")
                    .style.display = 'list-item';
            });
        }

        // not an instructional flight
        else {
            let pilotSelect = document.getElementById("allPilots");
            let pilotID = pilotSelect.value;

            if(pilotID <= 0)
            {
                // select the current user as the pilot by default
                pilotSelect.value = <?php echo $user_id; ?>;
                pilotID = <?php echo $user_id; ?>;
            }

            // check pilot medical certificate
            checkMedicalCertificate(pilotID, expirationDate)
            .then(function(response){
                let validMedCert = response['valid'];
                if(!validMedCert) {
                    document.getElementById("warning-pilot-med-cert")
                        .style.display = 'list-item';
                    $("#submit").attr("disabled", true);
                    $("#update_submit").attr("disabled", true);
                }
            })
            .catch(function(response){
                console.log("request failed");
                document.getElementById("warning-airw-generic")
                    .style.display = 'list-item';
            });

            // check solo student is endorsed
            checkIsStudent(pilotID).then(function(response){
                let pilotIsStudent = response['is_student'];
                if(pilotIsStudent)
                {
                    checkValidSoloEndorsement(pilotID, expirationDate);
                }
            });
        }
    }
}

/**
 * check if User is under their flight limit
 */
async function underFlightLimit(id) {
    return $.ajax({
        type: 'POST',
        url: '/api/user.php',
        data: JSON.stringify({
            id,
            mode: 'underFlightLimit'
        }),
        contentType: 'application/json',
        dataType: 'json'
    });
}

/**
 * Check if calendar object is of aircraft type
 */
function checkIfAircraft(id) {
    return $.ajax({
        type: 'POST',
        url: '/api/calendar_object.php',
        data: JSON.stringify({
            id,
            mode: 'isAircraft'
        }),
        contentType: 'application/json',
        dataType: 'json'
    });
}

/**
 * Check aircraft registration
 */
function checkAircraftRegistration(objectID, expirationDate) {
    $.ajax({
        type: 'POST',
        url: 'api/aircraft.php',
        data: JSON.stringify({
            mode: 'checkValidRegistration',
            id: objectID,
            expiration_date: expirationDate,
        }),
        contentType : 'application/json',
        dataType: 'json'
    })
    .then(function(response){
        let warning = document.getElementById('warning-aircraft-reg');

        if(!response['valid']) {
            warning.style.display = 'list-item';
            $("#submit").attr("disabled", true);
            $("#update_submit").attr("disabled", true);
        }
    })
    .fail(function(){
        let warning = document.getElementById('warning-airw-error');
        warning.style.display = 'list-item';
    });
}

/**
 * Check aircraft airworthiness cert
 */
function checkAircraftAirworthinessCert(objectID, expirationDate) {
    $.ajax({
        type: 'POST',
        url: 'api/aircraft.php',
        data: JSON.stringify({
            mode: 'checkValidAirworthinessCert',
            id: objectID,
            expiration_date: expirationDate,
        }),
        contentType : 'application/json',
        dataType: 'json'
    })
    .then(function(response){
        let warning = document.getElementById('warning-aircraft-airw-cert');

        if(!response['valid']) {
            warning.style.display = 'list-item';
            $("#submit").attr("disabled", true);
            $("#update_submit").attr("disabled", true);
        }
    })
    .fail(function(){
        let warning = document.getElementById('warning-airw-error');
        warning.style.display = 'list-item';
    });
}

/**
 * Check whether or not a user a student
 */
async function checkIsStudent(userID) {
    return $.ajax({
        type: 'POST',
        url: 'api/user.php',
        data: JSON.stringify({
            mode: 'checkIsStudent',
            id: userID,
        }),
        contentType : 'application/json',
        dataType: 'json'
    });
}

/**
 * Check medical certificate
 */
async function checkMedicalCertificate(pilotID, expirationDate) {
    return $.ajax({
        type: 'POST',
        url: 'api/user.php',
        data: JSON.stringify({
            mode: 'checkValidMedicalCert',
            id: pilotID,
            expiration_date: expirationDate,
        }),
        contentType : 'application/json',
        dataType: 'json'
    });
}

/**
 * Check valid solo endorsement
 */
function checkValidSoloEndorsement(pilotID, expirationDate) {
    $.ajax({
        type: 'POST',
        url: 'api/user.php',
        data: JSON.stringify({
            mode: 'checkValidSoloEndorsement',
            id: pilotID,
            expiration_date: expirationDate,
        }),
        contentType : 'application/json',
        dataType: 'json'
    })
    .then(function(response){
        let warning =
            document.getElementById('warning-student-solo-endorsement');

        if(!response['valid']) {
            warning.style.display = 'list-item';
            $("#submit").attr("disabled", true);
            $("#update_submit").attr("disabled", true);
        }
    })
    .fail(function(){
        let warning = document.getElementById('warning-airw-error');
        warning.style.display = 'list-item';
    });
}

function smValidate() {
    var valid = true;
    valid = checkEmpty($("#sm_calendar_object"));
    $("#sm_submit").attr("disabled",true);
    if(valid) {
        $("#sm_submit").attr("disabled",false);
    }
}

function checkEmpty(obj) {
    var name = $(obj).attr("name");
    $("."+name+"-validation").html("");	
    $(obj).css("border","");
    if($(obj).val() == "") {
        $(obj).css("border","#FF0000 1px solid");
        $("."+name+"-validation").html("Required");
        return false;
    }

    return true;
} 
</script>

