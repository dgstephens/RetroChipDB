<?php
namespace WhenYouFly\Scheduler;

require_once __DIR__.'/vendor/autoload.php';

include_once 'wyf_functions.php';
include_once 'fee_class.php';
include_once 'alert_class.php';

use WhenYouFly\Scheduler\Fee;
use \DateTime;
use \DateInterval;
use WhenYouFly\Scheduler\Alert;

class User {
    // other variables
    var $debug = 0;
    
    // Member Variables
    private $db_connection;
    private $userId;
    
    // Member Arrays
    private $userArray = array();

    // Creates a new user when a new client signs up
    static public function createNewAdminUser($db_connection, $user_vars)
    {
        $debug = 0;
        // Include vendor functions for e-mail functionality
        require_once('vendor/autoload.php');

        // INSERT NEW USER INFO
        $sql = "INSERT INTO users"
                . "( client_id, user_name, user_f_name, user_l_name, password, add_cal_entry, email_address )"
                . " VALUES( " . $user_vars['client_id'] . ", '" . $user_vars['user_name'] . "', '" . $user_vars['user_f_name']. "', '"
                . $user_vars['user_l_name'] . "', '" . $user_vars['password'] . "', 1, '"
                . $user_vars['client_admin_email'] . "' )";

        if( $debug == 1 )
        {
            echo "<br>" . $sql;        
        }
        $result = $db_connection->query( $sql );
        
        if (!$result)
        {
            echo "<br> New user creation failed. <br>";
            return -1;
        }

        // GET USER_ID
        $user_id = $db_connection->insert_id;

        // INSERT USER AS ADMIN IN DB
        $sql = "INSERT INTO admin_user"
                . "( user_id, admin_flag )"
                . " VALUES( " . $user_id . ", 1 )";

        if( $debug == 1 )
        {
            echo "<br>" . $sql;        
        }
        $result = $db_connection->query( $sql );
        
        if (!$result)
        {
            echo "<br> Insert user as Admin failed. <br>";
            return -1;
        }

        // ADD DEFAULT BANNER GRAPHICS
        // The default image is pulled from the database - see client_media table
        // to change
        $client_id = $user_vars['client_id'];
        
        $sql = "INSERT INTO client_media"
                . "( client_id )"
                . " VALUES( " . $client_id . " )";

        $result = $db_connection->query( $sql );
        if (!$result)
        {
            echo "<br> Insert user into client_media failed. <br>";
            return -1;
        }

        // GET MEDIA ID FOR BANNER
        $sql = "SELECT client_media_id "
                . " FROM client_media"
                . " WHERE client_id = $client_id";

        $result = $db_connection->query( $sql );
        $client_media_id = $result->fetch_row();

        // INSERT MEDIA ID INTO CLIENT_INFO
        $sql = "UPDATE client_info"
                . " SET client_media_banner = $client_media_id[0], client_media_banner_small = $client_media_id[0]"
                . " WHERE client_id = $client_id";

        if( $debug == 1 )
        {
            echo "<br>" . $sql;        
        }
        $result = $db_connection->query( $sql );

        //
        // SET UP DEFAULT PREFERENCES
        //

        // First Chart View Preferences
        $allObjectTypes = getAllObjectTypes( $db_connection);    

        foreach( $allObjectTypes as $objectType )
        {            
            $sql = "INSERT INTO prefs_client"
                    . " ( client_id, pref_type, pref_type_id, value)"
                    . " VALUES( $client_id, 'chart_view_items', " . $objectType['object_type_id'] .  ", '1' )";

            if( $debug == 1 )
            {
                echo "<br>" . $sql;                
            }

            $result = $db_connection->query( $sql );
        }

        // Create default object_sub_types (stored procedure)
        $sql = "CALL insert_default_object_sub_types( $client_id )";
        $result = $db_connection->query( $sql );
        
        // Create default squawk templates (stored procedure)
        $sql = "CALL insert_default_squawks( $client_id )";
        $result = $db_connection->query( $sql );

        // EMAIL USER WELCOME

        // We need our new client's information
        $user_f_name = $user_vars['user_f_name'];
        $user_l_name = $user_vars['user_l_name'];
        $email_address = $user_vars['client_admin_email'];

        //email new client
        $user_email_array = array
        (
            array( "email_address"=>$email_address ),
        );

        $msg_subject = "Welcome to WhenYouFly!";

        $msg_body = "Hello " . $user_f_name . ", and welcome to WhenYouFly!<br>"
                . "We are very excited to have you join us in our quest to make aircraft "
                . "scheduling, flight and maintenance tracking and, more generally, the "
                . "experience of flying just a little bit more wonderful.<br>"
                . "<p>"
                . "Please save your portal name: " . $user_vars['client_web_name'] . " someplace safe<br>"
                . "You will use this to login to your WhenYouFly site in the future. So "
                . "share it with all of your pilots so they can use the system as well.<br>"
                . "The URL is: www.whenyoufly.com/portal/" . $user_vars['client_web_name'] . "<br>"
                . "<p>" 
                . "You can also click the \"Log In\" link on the whenyoufly.com website home page "
                . "to log in; you'll need your portal name, login name and password. "
                . "<p>"
                . "You will also want to keep track of your login and password. Your login "
                . "is: " . $user_vars['user_name'] . " and we hope you remembered your password. "
                . "If you didn't please contact our support team and we'll reset it for you.<br>"
                . "<p>"
                . "Our support team is ready to help at any time. You can email us at: amelia@whenyoufly.com "
                . "or call us at 919-822-2288.<br>"
                . "<p>"
                . "From our aviation family to yours, thank you for choosing to share the gift "
                . "of aviation."
                . "<p>"
                . "Daniel and David and the WhenYouFly Team"; 


        multiEmailPhpMailer( "amelia@whenyoufly.com", "Amelia at WhenYouFly", 
                $user_email_array, "WhenYouFly", "", "", "", $msg_subject, $msg_body );

        // EMAIL WYF WE HAVE A NEW USER
        //email WYF
        $user_email_array = array
        (
            array( "email_address"=>"amelia@whenyoufly.com" ),
        );

        $msg_subject = "New User Signup!";

        $msg_body = "We have a new user!<br>"
                . "Their portal name (client_web_name) is: " . $user_vars['client_web_name'] . "<br>"
                . "Their admin user is: " . $user_vars['user_name'] . "<br>"
                . "Their email address is: " . $user_vars['client_admin_email'] . "<br>"
                . "Their client_id is: " . $client_id . "<br>"
                . "They may be reaching out shortly for support."
                . "<p>"
                . "The management.";                    

        multiEmailPhpMailer( "amelia@whenyoufly.com", "Amelia at WhenYouFly", 
                $user_email_array, "WhenYouFly", "", "", "", $msg_subject, $msg_body );
        
        return $user_id;
    }

    /**
     * Create a new ghost user (used for things like AD/endorsement signatories)
     */
    static public function createGhostUser( $db_connection, $user_f_name,
        $user_l_name, $pilot_cert_number )
    {
        // check if we are already tracking the pilot cert number
        $sql = 'SELECT user_id FROM users'
            . ' WHERE pilot_cert_number=?;';

        $stmt = $db_connection->prepare( $sql );
        $stmt->bind_param( 'i', $pilot_cert_number );
        $stmt->execute();

        $result = $stmt->get_result();
        // if a valid user_id is found, return it
        if( $result->num_rows > 0 and $result['user_id'] > 0 )
        {
            return $result['user_id'];
        }

        $sql = 'INSERT INTO users (user_f_name, user_l_name, pilot_cert_number,'
            . ' active_user, ghost_user)'
            . ' VALUES (?, ?, ?, 0, 1);';

        $stmt = $db_connection->prepare( $sql );
        $stmt->bind_param( 'ssi', $user_f_name, $user_l_name,
            $pilot_cert_number);
        $stmt->execute();

        // get the database assigned ID
        $sql = 'SELECT LAST_INSERT_ID() AS id;';
        $result = $db_connection->query( $sql );
        if( $result->num_rows > 0 )
        {
            return $result->fetch_assoc()['id'];
        }

        return NULL;
    }
    
    
    /**
     * Create a new ghost user (used for things like AD/endorsement signatories)
     */
    static public function checkUserName( $conn, $username, $client_id)
    {
        // check if we are already tracking the pilot cert number
        $sql = "SELECT COUNT(*) FROM users"
            . " WHERE client_id=?"
            . " AND user_name LIKE '$username%'";

        $stmt = $conn->prepare( $sql );
        $stmt->bind_param( 'i', $client_id );
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_row()[0];
    }
    

    /**
     * Get all users
     *
     * returns an array of all users and all their information
     * current flags are all_users or all_pilots
     *
     * This function is meant to replace a function in wyf_functions.php of the
     * same name.
     */
    static public function getAllUsers( $db_connection, $client_id, $flag )
    {
        $debug = 0;

        // GET ALL USERS
        if( $flag == "all_users" )
        {
            $sql = "SELECT * FROM users"
                . " WHERE client_id = " . $client_id
                . " ORDER BY user_l_name ASC";

            if( $debug == 1 )
            {
                echo "<br>" . $sql . "<br>";
            }
            $result = $db_connection->query( $sql );

            for( $all_users_array = array ();
                $row = $result->fetch_assoc();
                $all_users_array[] = $row );
        }

        // GET ACTIVE USERS
        if( $flag == "active_users" )
        {
            $sql = "SELECT * FROM users"
                . " WHERE client_id = " . $client_id
                . " AND active_user = 1"
                . " ORDER BY user_l_name ASC";

            if( $debug == 1 )
            {
                echo "<br>" . $sql . "<br>";
            }
            $result = $db_connection->query( $sql );

            for( $all_users_array = array ();
                $row = $result->fetch_assoc();
                $all_users_array[] = $row );
        }

        // GET ALL (active) PILOTS
        else if( $flag == "all_pilots" || $flag == "charter_pilots"
            || $flag == "co_pilots" || $flag == "instructors"
            || $flag == "all_students" )
        {
             $sql = "SELECT * FROM users"
                . " WHERE client_id = " . $client_id
                . " AND active_user = 1"
                . " ORDER BY user_l_name ASC";

            if( $debug == 1 )
            {
                echo "<br>" . $sql . "<br>";
            }
            $result = $db_connection->query( $sql );

            for( $temp_users_array = array ();
                $row = $result->fetch_assoc();
                $temp_users_array[] = $row );

            // now get user_ids with roles other than 4 (administrator)
            $sql = "SELECT DISTINCT user_id FROM user_roles"
                    . " WHERE user_id IN "
                    . " (SELECT user_id FROM users"
                    . " WHERE client_id = " . $client_id . ")";

            if( $flag == "all_pilots ")
                $sql = $sql . " AND role_id <> 4";

            else if( $flag == "charter_pilots" )
                $sql = $sql . " AND role_id = 3";

            else if( $flag == "co_pilots" )
                $sql = $sql . " AND role_id = 3 OR role_id = 6";

            else if( $flag == "instructors" )
                $sql = $sql . " AND role_id = 2";

            else if( $flag == "all_students" )
                $sql = $sql . " AND role_id = 5";

            if( $debug == 1 )
            {
                echo "<br>" . $sql . "<br>";
            }

            $result = $db_connection->query( $sql );

            for( $all_roles_array = array ();
                $row = $result->fetch_assoc();
                $all_roles_array[] = $row );

            $all_users_array = array();

            foreach( $temp_users_array as $user )
            {
                foreach( $all_roles_array as $role )
                {
                    if( $user[ 'user_id' ] == $role[ 'user_id' ] )
                    {
                        if( $debug == 1 )
                        {
                            echo "<br>user_id " . $user[ 'user_id' ]
                                . " was tagged";
                        }

                        $all_users_array[] = $user;
                    }
                }
            }
        }

        if( $debug == 1 )
            print_r( $all_users_array );

        return $all_users_array;
    }

    //CONSTUCTOR
    function __construct( $db_connection, $userId )
    {
        $this->db_connection = $db_connection;
        $this->userId = $userId;
        
        // Get USER details
        $sql = "SELECT *"
                . " FROM users"
                . " WHERE user_id = " . $this->userId;       
        
        $result = $this->db_connection->query( $sql );

        if( $result->num_rows > 0 )
        {
            $this->userArray = $result->fetch_assoc();            
        }
    }
    
    
    /*
     * 
     *      GOOGLE CALENDAR FUNCTIONALITY
     * 
     */
    
    public function setGoogleAccessToken($token)
    {
        $json_token = json_encode($token);
        $sql = "UPDATE users"
            . " SET google_access_token=?"
            . " WHERE user_id=?";
        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'si', $json_token, $this->userId );
        return $stmt->execute();
    }
    
    public function revokeGoogleAccessToken()
    {
        $json_token = "";
        $sql = "UPDATE users"
            . " SET google_access_token=?"
            . " WHERE user_id=?";
        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'si', $json_token, $this->userId );
        return $stmt->execute();
    }
    
    public function getGoogleAccessToken()
    {
        $sql = "SELECT google_access_token FROM users"
            . " WHERE user_id=?";
        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'i', $this->userId );
        $stmt->execute();
        $result = $stmt->get_result();
        $access_token = $result->fetch_assoc()['google_access_token'];
        if ($access_token === "")
        {
            return false;
        }
        else
        {
            return json_decode($access_token, true);
        }
    }
    
    public function isGoogleAuthorized()
    {
        $sql = "SELECT google_access_token FROM users"
            . " WHERE user_id=?";
        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'i', $this->userId );
        $stmt->execute();
        $result = $stmt->get_result();
        $access_token = $result->fetch_assoc()['google_access_token'];
        if ($access_token === "")
        {
            return false;
        }
        else
        {
            return true;
        }
    }
    
    public function addGoogleCalendarEvent($event_array)
    {   
        $g_client = new \Google\Client();
        $g_client->setAuthConfig('creds/client_secret.json');
        $g_client->setAccessToken( $this->getGoogleAccessToken() );
        $g_calendar = new \Google_Service_Calendar($g_client);
        $event = new \Google_Service_Calendar_Event($event_array);
        try {
            $event = $g_calendar->events->insert('primary', $event);
        } catch (\Google_Service_Exception $e ) {
            return "";
        }
        return $event['id'];
    }
    
    public function updateGoogleCalendarEvent($google_event_id, $event_array)
    {
        $g_client = new \Google\Client();
        $g_client->setAuthConfig('creds/client_secret.json');
        $g_client->setAccessToken( $this->getGoogleAccessToken() );
        $g_calendar = new \Google_Service_Calendar($g_client);
        $event = new \Google_Service_Calendar_Event($event_array);
        try {
            $event = $g_calendar->events->update('primary', $google_event_id, $event);
        } catch (\Google_Service_Exception $e ) {
            return "";
        }
        return $event['id'];
    }
    
    public function deleteGoogleCalendarEvent($google_event_id)
    {
        $g_client = new \Google\Client();
        $g_client->setAuthConfig('creds/client_secret.json');
        $g_client->setAccessToken( $this->getGoogleAccessToken() );
        $g_calendar = new \Google_Service_Calendar($g_client);
        try {
            $event = $g_calendar->events->delete('primary', $google_event_id);
        } catch (\Google_Service_Exception $e ) {
            return false;
        }
        return true;
    }
    
    
    public function getExpirationAlerts()
    {
        $alert_array = ['record_alerts' => ['expired' => array(), 'upcoming' => array()], 'endorsement_alerts' => ['expired' => array(), 'upcoming' => array()] ];
        $today = date("Y-m-d");
        
        // Get expiring Records
        $sql = "SELECT record_id, pilot_id, user_id, name, expiration_date,"
            . " DATEDIFF(expiration_date, ?) as days_until,"
            . " record_categories.category_string"
            . " FROM records"
            . " INNER JOIN record_categories"
            . "     ON record_categories.category_id = records.category"
            . " WHERE pilot_id=?"
            . " AND DATEDIFF(expiration_date, ?) <= 30"
            . " AND hidden=0"
            . " ORDER BY expiration_date ASC;";

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( "sis", $today, $this->userId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        if( $result->num_rows > 0 )
        {
            while ($row = $result->fetch_assoc() )
            {
                if ($row['days_until'] < 0)
                {
                    $alert_array['record_alerts']['expired'][] = $row;
                }
                else
                {
                    $alert_array['record_alerts']['upcoming'][] = $row;
                }
            }
        }
        
        
        // Get expiring Endorsements
        $sql = "SELECT endorsement_data.expiration_date,"
            . " DATEDIFF(expiration_date, ?) as days_until,"
            . " endorsement_templates.title"
            . " FROM endorsement_data"
            . " INNER JOIN endorsement_templates"
            . "     ON endorsement_data.template_id=endorsement_templates.id"
            . " WHERE endorsement_data.pilot_id=?"
            . " AND DATEDIFF(endorsement_data.expiration_date, ?) <= 30"
            . " AND hidden=0"
            . " ORDER BY endorsement_data.expiration_date ASC;";

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'sis', $today, $this->userId, $today );
        $stmt->execute();
        $result = $stmt->get_result();
        if( $result->num_rows > 0 )
        {
            while ($row = $result->fetch_assoc() )
            {
                if ($row['days_until'] < 0)
                {
                    $alert_array['endorsement_alerts']['expired'][] = $row;
                }
                else
                {
                    $alert_array['endorsement_alerts']['upcoming'][] = $row;
                }
            }
        }
        
        return $alert_array;
    }
    
    
//    Gets all User Groups of which the User is a part.
//    
//    Returns:
//        An array of 'group_id's.
    
    public function getGroups()
    {
        $sql = "SELECT group_id"
            . " FROM user_groups"
            . " WHERE user_id=$this->userId";
        $result = $this->db_connection->query($sql);
        
        $group_ids = array();
        if( $result->num_rows > 0 )
        {
            while ( $group = $result->fetch_row() )
            {
                $group_ids[] = $group[0];
            }
        }
        return $group_ids;
    }
    
    
//    Gets all recurring Fees that would trigger within the specified date range.
//    
//    Parameters:
//        $start_date - The beginning of the date range, inclusive.
//        $end_date - The end of the date range, inclusive.
//    Returns:
//        An array of associative arrays where each associative array is the info 
//        of a single instance of a Fee.
            
    public function getRecurringFees($start_date, $end_date)
    {
        $assigned_fees = $this->getAssignedRecurringFees();
        $fee_arr = array();
        foreach($assigned_fees as &$fee)
        {
            $date_arr = Fee::getRecurringDates($start_date, $end_date, $fee, $this->db_connection);
            foreach($date_arr as $date)
            {
                if ( !$this->recurFeeExists($fee['fee_id'], $date) )
                {
                    $fee['id'] = 'recur_' . $fee['fee_id'];
                    $fee['date'] = $date;
                    $fee['billed'] = 0;
                    $fee['user_l_name'] = $this->userArray['user_l_name'];
                    $fee['full_name'] = $this->userArray['user_f_name'] . ' ' . $this->userArray['user_l_name'];
                    $fee_arr[] = $fee;
                }
            }
        }
        return $fee_arr;
    }
    
    
//    Gets all recurring Fees that have been assigned to the User.
//    
//    Returns:
//        An array of associative arrays where each associative array is the info
//        of a single assigned recurring Fee.

            
    public function getAssignedRecurringFees()
    {
        $sql = "SELECT user_recur_fees.recur_fee_id,"
            . " user_recur_fees.user_id,"
            . " user_recur_fees.fee_id,"
            . " client_fees.description,"
            . " client_fees.amount,"
            . " client_fees.date,"
            . " client_fees.type,"
            . " client_fees.qb_item_id,"
            . " users.qb_id"
            . " FROM user_recur_fees"
            . " INNER JOIN client_fees"
            . "     ON client_fees.fee_id=user_recur_fees.fee_id"
            . " INNER JOIN users"
            . "     ON users.user_id=user_recur_fees.user_id"
            . " WHERE user_recur_fees.user_id=$this->userId"
            . " ORDER BY client_fees.type ASC, client_fees.amount ASC";
        $result = $this->db_connection->query($sql);
        if ($result)
        {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        else
        {
            return NULL;
        }
    }
    
    
//    Gets all recurring Fee IDs that have been assigned to the User.
//    
//    Returns:
//        An array of associative arrays where each row is a single 'fee_id'
    public function getAssignedRecurringFeeIDs()
    {
        $sql = "SELECT user_recur_fees.fee_id"
            . " FROM user_recur_fees"
            . " WHERE user_recur_fees.user_id=$this->userId";
        $result = $this->db_connection->query($sql);
        if ($result)
        {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        else
        {
            return NULL;
        }
    }
    
    
//    Create an instance of a Fee linked to this User.
//    
//    Parameters:
//        $fee_id - The 'fee_id' of the Fee from the 'client_fees' table.
//        $date - The date when the Fee is assessed.
//    Returns:
//        The 'id' of the assessed Fee if the insert succeeds. 'False' otherwise.
//        public function createFee($fee_id, $date)
    
    public function createFee( $fee_id, $date )
    {
        $sql = "INSERT INTO user_fees"
            . " (user_id, fee_id, date)"
            . " VALUES ($this->userId, $fee_id, '$date')";
        $result = $this->db_connection->query($sql);
        if ($result)
        {
            return $this->db_connection->insert_id;
        }
        else
        {
            return false;
        }
    }
    
    
//    Check whether or not a given instance of a Fee already exists in the database.
//
//    Parameters:
//        $fee_id - The 'fee_id' of the Fee from the 'client_fees' table.
//        $date - The date when the Fee is assessed.
//    Returns:
//        '1' if the entry already exists. '0' otherwise.
    
    public function recurFeeExists($fee_id, $date)
    {
        $sql = "SELECT EXISTS"
            . " (SELECT 1 FROM user_fees"
            . " WHERE fee_id=$fee_id"
            . " AND date='$date'"
            . " AND user_id=$this->userId)";
        $result = $this->db_connection->query($sql);
        return $result->fetch_row()[0];
    }
    
    
//    Assign a recurring Fee to this User. This could be a recurring Fee for this 
//    individual User, or a Group Recurring Fee for the Group of which the User 
//    is a part. Since it is possible for a User to be assigned an individual 
//    recurring Fee and then have that same recurring Fee be assigned to them 
//    through a Group, the individual recurring Fee will be overwritten with the 
//    Group Recurring Fee information in the event of a double assignment.
//
//    Parameters:
//        $fee_id - The 'fee_id' of the Fee from the 'client_fees' table.
//        $group_fee_id - The 'group_fee_id' from the 'group_recur_fees' table 
//          if this is a Group Recurring Fee. If this recurring Fee is not associated 
//          with a User Group, then this variable is an empty string.
//    Returns:
//        'True' if the sql query works. 'False' otherwise.
    
    public function assignRecurFee($fee_id, $group_fee_id)
    {
        if ($group_fee_id == "")
        {
            $sql = "INSERT INTO user_recur_fees"
            . " (user_id, fee_id)"
            . " VALUES ($this->userId, $fee_id)";
        }
        else
        {
            $sql = "SELECT EXISTS (SELECT * FROM user_recur_fees WHERE user_id=$this->userId AND fee_id=$fee_id)";
            $result = $this->db_connection->query($sql);
            if ($result->fetch_row()[0] == 1)
            {
                $sql = "UPDATE user_recur_fees"
                . " SET group_fee_id=$group_fee_id"
                . " WHERE user_id=$this->userId"
                . " AND fee_id=$fee_id";
            }
            else
            {
                $sql = "INSERT INTO user_recur_fees"
                . " (user_id, fee_id, group_fee_id)"
                . " VALUES ($this->userId, $fee_id, $group_fee_id)";
            }
        }
        $result = $this->db_connection->query($sql);
        if ($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
//    Remove the specified Group Recurring Fee from this User.
//
//    Parameters:
//        $group_fee_id - The 'group_fee_id' of the recurring fee from the 
//        'group_recur_fees' table.
//    Returns:
//        'True' if the sql query works. 'False' otherwise.
            
    public function deleteRecurGroupFee($group_fee_id)
    {
        $sql = "DELETE FROM user_recur_fees"
            . " WHERE group_fee_id=$group_fee_id";
        $result = $this->db_connection->query($sql);
        
        if ($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    
    /**
     * Get the number off flights already scheduled for this User scheduled on
     * or after today's date
     *
     * This does not include maintenance scheduled by this User or deleted
     * entries.
     */
    public function getNumberOfFutureFlights()
    {
        // today's date
        $date = date('Y-m-d', time());

        $sql = 'SELECT COUNT(*) AS no_of_flights'
            . ' FROM calendar'
            . ' WHERE user_id=? AND from_date>=? AND deleted <> 1;';

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'is', $this->userId, $date );
        $stmt->execute();

        $result = $stmt->get_result();
        if( $result->num_rows > 0 )
        {
            return $result->fetch_assoc()['no_of_flights'];
        }

        return 0;
    }

    
//    Get all upcoming flights and their data for this User from the current day 
//    onward.
//
//    Returns:
//        If the query succeeds, an array of associative arrays where each 
//        associative array is a single flight. If the query fails, the 
//        function returns NULL.
            
    public function getUpcomingFlights()
    {
        $timestamp = date('Y-m-d H:i:s');
        $sql = "SELECT calendar.object_id, from_date, to_date, from_time, to_time,"
            . " subject, pilot_2, instructor_id, calendar_objects.object_name,"
            . " checked_in, entry_id, object_sub_types.object_type_id"
            . " FROM calendar"
            . " INNER JOIN calendar_objects"
            . "     ON calendar_objects.object_id=calendar.object_id"
            . " INNER JOIN object_sub_types"
            . "     ON object_sub_types.object_sub_type_id=calendar_objects.object_sub_type"
            . " WHERE (calendar.user_id=? OR calendar.instructor_id=?"
            . " OR calendar.pilot_2=?)"
            . " AND object_sub_types.object_type_id=128" 
            . " AND TIMESTAMP(from_date, from_time) >= ?";

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'iiis', $this->userId, $this->userId, $this->userId,
            $timestamp );
        $stmt->execute();

        if( !empty( $stmt->error ))
        {
            return NULL;
        }

        $result = $stmt->get_result();
        if( $result->num_rows > 0 )
        {
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        return array();
    }

    /**
     * Get Item access values: boolean values for each plane in the client's
     * item list determining whether or not the pilot is allowed to schedule the
     * item
     */
    public function getItemAccess()
    {
        // this gets all client's aircraft, and sets access_allowed for each
		// that the user is allowed to schedule
		$sql = 'SELECT object_name, calendar_objects.object_id, aircraft_type_flag,'
			. '     IF(user_item_restrictions.object_id IS NULL, TRUE, FALSE)'
			. '     AS access_allowed'
			. ' FROM (SELECT * FROM user_item_restrictions WHERE user_id=?)'
			. '     AS user_item_restrictions'
			. ' RIGHT JOIN'
			. '     (SELECT object_name, object_id, IF(object_type_id=128,'
			. '     TRUE, FALSE) as aircraft_type_flag'
			. '     FROM calendar_objects'
			. '     JOIN object_sub_types'
			. '     ON calendar_objects.object_sub_type=object_sub_types.object_sub_type_id'
			. '     WHERE calendar_objects.client_id=?) AS calendar_objects'
			. ' ON user_item_restrictions.object_id=calendar_objects.object_id'
            . ' ORDER BY object_name';

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'ii', $this->userArray['user_id'],
            $this->userArray['client_id'] );

        $stmt->execute();
        $result = $stmt->get_result();

        // handle no-row error
        if( $result->num_rows < 1 )
        {
            return NULL;
        }

        // return a nice array of the results
        for( $item_access = array();
            $row = $result->fetch_assoc();
            $item_access[] = $row);

        return $item_access;
    }

    /**
     * Set access for a particular Item
     */
    public function setItemAccess( $object_id, $access_allowed )
    {
        if( $access_allowed )
        {
            // delete the restriction
            $sql = 'DELETE FROM user_item_restrictions'
                . ' WHERE user_id=? AND object_id=?;';

            $stmt = $this->db_connection->prepare( $sql );
            $stmt->bind_param( 'ii', $this->userId, $object_id );
            $stmt->execute();

            return true;
        } else {
            // ensure there is a restriction
            $sql = 'INSERT IGNORE INTO user_item_restrictions (user_id,'
                . ' object_id)'
                . ' VALUES (?, ?);';

            $stmt = $this->db_connection->prepare( $sql );
            $stmt->bind_param( 'ii', $this->userId, $object_id );
            $stmt->execute();

            return true;
        }
    }

    /**
     * Get the number of flights this User is allowed to schedule in advance
     */
    public function getFlightLimit()
    {
        $sql = 'SELECT flight_limit'
            . ' FROM users'
            . ' WHERE user_id=?;';

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'i', $this->userId );
        $stmt->execute();

        $result = $stmt->get_result();

        if( $result->num_rows > 0)
        {
            return $result->fetch_assoc()['flight_limit'];
        }

        return NULL;
    }

    /**
     * Set the number of flights this User is allowed to schedule in advance
     */
    public function setFlightLimit( $n_flights )
    {
        $sql = 'UPDATE users'
            . ' SET flight_limit=?'
            . ' WHERE user_id=?;';

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'ii', $n_flights, $this->userId );
        $stmt->execute();

        // TODO handle last SQL error here
        if( $stmt->error )
        {
            return False;
        }

        return True;
    }

    // GETTERS
    public function getSquawks($sort_value, $sort_type)
    {
        $sec_sort_value = "date_submitted";
        if ($sort_value == "date_submitted")
        {
            $sec_sort_value = "date_assigned";
        }
        
        $sql = "SELECT squawks.squawk_id,"
            . " squawks.details,"
            . " squawks.hidden,"
            . " squawks.pilot_hidden,"
            . " squawks.date_submitted,"
            . " squawks.date_assigned,"
            . " squawks.date_completed,"
            . " squawks.user_id_reported,"
            . " squawk_templates.squawk_str,"
            . " squawk_categories.cat_str,"
            . " calendar_objects.object_id,"
            . " calendar_objects.object_name,"
            . " CONCAT(users.user_f_name,' ',users.user_l_name) AS full_name"
            . " FROM squawks"
            . " INNER JOIN squawk_templates"
            . "     ON squawks.squawk_template_id=squawk_templates.squawk_template_id"
            . "     AND user_id_reported=$this->userId"
            . " INNER JOIN squawk_categories"
            . "     ON squawk_templates.cat_id=squawk_categories.cat_id"
            . " INNER JOIN calendar_objects"
            . "     ON squawks.object_id=calendar_objects.object_id"
            . " INNER JOIN users"
            . "     ON squawks.user_id_reported=users.user_id"
            . " ORDER BY $sort_value $sort_type, $sec_sort_value $sort_type, squawk_id $sort_type";
        $result = $this->db_connection->query( $sql );
        if( $result->num_rows < 1 )
            return NULL;

        $squawks_array = array();
        while ($row = $result->fetch_assoc() )
        {
            $squawks_array[] = $row;
        }
        return $squawks_array;
    }
    
    // GETTERS
    public function getJsonUserData()
    {
        echo json_encode( $this->userArray );
    }
    
    public function getUserData()
    {
        return( $this->userArray );
    }
    
    /**
     * Get string with user's email address
     */
    public function getEmailAddress()
    {
        return $this->userArray['email_address'];
    }

    /**
     * Get string with user's first name
     */
    public function getFirstName()
    {
        return $this->userArray['user_f_name'];
    }

    /**
     * Get string with user's last name
     */
    public function getLastName()
    {
        return $this->userArray['user_l_name'];
    }

    /**
     * Get string with user's first and last name separated by a space
     */
    public function getName()
    {
        return $this->userArray['user_f_name'] . " "
             . $this->userArray['user_l_name'];
    }
    
    /**
     * Get string with user's login name
     */
    public function getLoginName()
    {
        return $this->userArray['user_name'];
    }

    /**
     * Get pilot certification number (this is the same as A+P and I/A numbers)
     */
    public function getPilotCertNumber()
    {
        return $this->userArray['pilot_cert_number'];
    }

    // INSTRUCTOR's INFORMATION
    // returns ARRAY of all instructor's data
    public function getPilotInstructorInfo()
    {
        $sql = "SELECT *"
                . " FROM users"
                . " WHERE user_id = "
                . " ( SELECT cfi_id FROM student_cfi WHERE student_id = " . $this->userArray['user_id'] . ")";        
        
        $result = $this->db_connection->query( $sql );
        
        if( $result->num_rows > 0 )
        {
            return( $result->fetch_assoc() );
        } else {
            return 0;
        }
    }
    
    public function editInstructorRate($rate, $qb_item_id)
    {
        $sql = "SELECT EXISTS(SELECT * FROM instructor_rates"
            . " WHERE instructor_id=?)";
        
        $stmt = $this->db_connection->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->fetch_row()[0] == 0)
        {
            $sql = "INSERT INTO instructor_rates"
                . " (instructor_id, rate, qb_item_id)"
                . " VALUES (?, ?, ?)";

            $stmt = $this->db_connection->prepare($sql);
            $stmt->bind_param("idi", $this->userId, $rate, $qb_item_id);
            $result = $stmt->execute();
            if ($result)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            $sql = "UPDATE instructor_rates"
                . " SET rate=?,"
                . " qb_item_id=?"
                . " WHERE instructor_id=?";

            $stmt = $this->db_connection->prepare($sql);
            $stmt->bind_param("dii", $rate, $qb_item_id, $this->userId);
            $result = $stmt->execute();
            if ($result)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function updateInstructorQBItem($qb_item_id)
    {
            $sql = "UPDATE instructor_rates"
                . " SET qb_item_id=?"
                . " WHERE instructor_id=?";

            $stmt = $this->db_connection->prepare($sql);
            $stmt->bind_param("ii", $qb_item_id, $this->userId);
            return $stmt->execute();
    }

    public function getClientID()
    {
        return $this->userArray['client_id'];
    }
    
    /*
     *  Get the full name of the Client to which this User belongs
     */
    public function getClientLongName()
    {
        $stmt = $this->db_connection->prepare("SELECT client_long_name FROM client_info WHERE client_id=?");
        $stmt->bind_param("i", $this->userArray['client_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_row()[0];
    }
    
    /*
     *  Get the portal name of the Client to which this User belongs
     */
    public function getPortalName()
    {
        $stmt = $this->db_connection->prepare("SELECT client_web_name FROM client_info WHERE client_id=?");
        $stmt->bind_param("i", $this->userArray['client_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_row()[0];
    }
    
    public function getQBID()
    {
        return $this->userArray['qb_id'];
    }
    
    // returns "<firstname> <lastname>" as a string
    public function getPilotInstructorName()
    {
        return( $this->getPilotInstructorInfo()['user_f_name'] . " " . $this->getPilotInstructorInfo()['user_l_name'] );
    }
    
    // OTHER GOODIES
    
    // Is this pilot an administrator?
    public function isAdministrator()
    {
        $sql = "SELECT role_id"
                . " FROM user_roles"
                . " WHERE role_id = 4"
                . " AND user_id = " . $this->userArray['user_id'];
        
        $result = $this->db_connection->query( $sql );
        
        if( $result->num_rows > 0 )
        {
            return 1;
        } else {
            return 0;
        }     
    }
    
    // Is this pilot an admin?
    public function isAdmin()
    {
        $sql = "SELECT admin_flag"
            . " FROM admin_user"
            . " WHERE user_id=?;";

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'i', $this->userId );
        $stmt->execute();

        $result = $stmt->get_result();

        if( $result->num_rows > 0 && $result->fetch_assoc()['admin_flag'] )
        {
            return true;
        }

        return false;
    }

    // Check Roles
    // return true if user has the specified role
    // return false if not
    public function checkRole( $roleId )
    {
        $sql = "SELECT role_id"
                . " FROM user_roles"
                . " WHERE role_id = " . $roleId
                . " AND user_id = " . $this->userArray['user_id'];
        
        $result = $this->db_connection->query( $sql );
        
        if( $result->num_rows > 0 )
        {
            return 1;
        } else {
            return 0;
        }
    }
    
    // get highest certificate rating
    public function getCertificateId()
    {
        $sql = "SELECT certificate_type"
                . " FROM user_certificates"
                . " WHERE user_id = " . $this->userArray['user_id'];
        
        $result = $this->db_connection->query( $sql );
        
        if( $result->num_rows > 0 )
        {
            $row = $result->fetch_assoc();
            
            return( $row['certificate_type']);
        } else {
            return 0;
        }
    }
    
    public function getCertificateName()
    {
        $sql = "SELECT certificate_name"
                . " FROM certificate_types"
                . " WHERE certificate_type_id = "
                . "     ( SELECT certificate_type"
                . "     FROM user_certificates"
                . "     WHERE user_id = " . $this->userArray['user_id'] . ")";        
        
        $result = $this->db_connection->query( $sql );
        
        if( $result->num_rows > 0 )
        {
            $row = $result->fetch_assoc();
            
            return( $row['certificate_name'] );
        } else {
            return 0;
        }
    }

    /**
     * Returns the User ID of the pilot with the given pilot certification
     * number. If the cert. number is not found in the system, -1 is returned.
     */
    public static function getUserIDByPilotCertNumber( $conn, $cert_number )
    {
        $sql = "SELECT user_id "
             . "FROM users "
             . "WHERE pilot_cert_number=?";

        $stmt = $conn->prepare( $sql );
        $stmt->bind_param( "i", $cert_number );
        $stmt->execute();

        $result = $stmt->get_result();
        if( $result->num_rows > 0 )
        {
            return $result->fetch_assoc()['user_id'];
        }

        return -1;
    }

    /**
     * Check for a valid medical certificate Record.
     *
     * The caller optionally can provide a date (NOTE: MUST BE OF YYYY/mm/dd)
     */
    public function checkValidMedicalCertificate( $date=NULL )
    {
        // default to tomorrow
        if( !isset( $date ))
        {
            $date = date( 'Y/m/d', strtotime( '+1 day' ));
        }

        $sql = 'SELECT EXISTS('
            . '     SELECT * FROM records'
            . '     WHERE pilot_id=? AND category=4 AND'
            . '         DATE(expiration_date) BETWEEN ? AND "9999-12-31")'
            . ' AS row_exists;';

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'is', $this->userId, $date );
        $stmt->execute();

        $result = $stmt->get_result();

        if( $result->num_rows > 0 && $result->fetch_assoc()['row_exists'] )
        {
            return true;
        }

        return false;
    }

    /**
     * Check for a valid solo Endorsement.
     *
     * The caller optionally can provide a date (NOTE: MUST BE OF YYYY/mm/dd)
     */
    public function checkValidSoloEndorsement( $date=NULL )
    {
        // default to tomorrow
        if( !isset( $date ))
        {
            $date = date( 'Y/m/d', strtotime( '+1 day' ));
        }

        $sql = 'SELECT EXISTS('
            . '     SELECT * FROM endorsement_data'
            . '     WHERE pilot_id=? AND template_id=3 AND'
            . '         DATE(expiration_date) BETWEEN ? AND "9999-12-31")'
            . ' AS row_exists;';

        $stmt = $this->db_connection->prepare( $sql );
        $stmt->bind_param( 'is', $this->userId, $date );
        $stmt->execute();

        $result = $stmt->get_result();

        if( $result->num_rows > 0 && $result->fetch_assoc()['row_exists'] )
        {
            return true;
        }

        return false;
    }
    
    static public function createNewUser($db_connection, $user_vars)
    {
        // INSERT NEW USER INFO
        $sql = "INSERT INTO users"
                . " (client_id, user_name, user_f_name, user_l_name, password,"
                . " email_address, street_address, city_address, state_address,"
                . " zip_address, phone_number_mobile, join_date, dob)"
                . " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db_connection->prepare( $sql );
        $stmt->bind_param( 'issssssssssss', 
                        $user_vars['client_id'], 
                        $user_vars['user_name'], 
                        $user_vars['user_f_name'], 
                        $user_vars['user_l_name'], 
                        $user_vars['password'], 
                        $user_vars['email_address'],
                        $user_vars['street_address'],
                        $user_vars['city_address'],
                        $user_vars['state_address'],
                        $user_vars['zip_address'],
                        $user_vars['phone_number_mobile'],
                        $user_vars['join_date'],
                        $user_vars['dob']);
        $stmt->execute();
        $new_user_id = $db_connection->insert_id;
        Alert::sendNewUserEmail($db_connection, $new_user_id, $user_vars['raw_password']);
        
        return $new_user_id;
    }
}
