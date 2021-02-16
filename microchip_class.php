<?php
namespace MicroChipDB;

require_once __DIR__.'/vendor/autoload.php';

include_once 'retro_functions.php';

class Microchip {
    // other variables
    var $debug = 0;
    
    // Member Variables
    private $db_connection;
    private $userId;
    private $microchipId;
    
    // Member Arrays
    private $microchipArray = array();

    //CONSTUCTOR
    function __construct( $db_connection, $microchipId )
    {
        $this->db_connection = $db_connection;
        $this->microchipId = $microchipId;
        
        // Get USER details
        $sql = "SELECT *"
                . " FROM microchips"
                . " WHERE microchip_id = " . $this->microchipId;       
        
        $result = $this->db_connection->query( $sql );

        if( $result->num_rows > 0 )
        {
            $this->microchipArray = $result->fetch_assoc();            
        }
    }

    static public function getAllMicrochips( $db_connection, $microchip_id, $flag )
    {
        $debug = 0;

        // GET ALL microchips (includes unmoderated chips)
        if( $flag == "all_microchips" )
        {
            $sql = "SELECT * FROM microchips"
                . " WHERE client_id = " . $client_id
                . " ORDER BY chip_name ASC";

            if( $debug == 1 )
            {
                echo "<br>" . $sql . "<br>";
            }
            $result = $db_connection->query( $sql );

            for( $all_microchips_array = array ();
                $row = $result->fetch_assoc();
                $all_microchips_array[] = $row );
        }

        // GET MODERATED MICROCHIPS
        if( $flag == "moderated_microchips" )
        {
            $sql = "SELECT * FROM microchips"
                . " WHERE moderated = 1"
                . " ORDER BY chip_name ASC";

            if( $debug == 1 )
            {
                echo "<br>" . $sql . "<br>";
            }
            $result = $db_connection->query( $sql );

            for( $all_microchips_array = array ();
                $row = $result->fetch_assoc();
                $all_users_array[] = $row );
        }

        if( $debug == 1 )
            print_r( $all_microchips_array );

        return $all_microchips_array;
    }