<?php

namespace Bookworm;

use \Bookworm\Database;

class Driver {

    /**
     * @var array
     */
    public static $drivers = [];
    
    /**
     * @brief register a new connection in the drivers class, this way we can 
     * access it everywhere. 
     * @method registerConnection
     * @param string $connection
     * @param array $data
     * @return \Bookworm\Database
     */
    public static function registerConnection( $connection, $data ){
        if( isset(self::$drivers[ $connection ])){
            return self::$drivers[ $connection ];
        }
        self::$drivers[ $connection ] = new Database( $data );
        return self::$drivers[ $connection ];
    }
    
    /**
     * @brief retrieve a connection object from the driver store. 
     * @method getConnection
     * @param string $connection
     * @return \Bookworm\Database $driver 
     */
    public static function getConnection( $connection ){
        if( self::$drivers[ $connection ]){
            return self::$drivers[ $connection ];
        }
        throw new \Exception('The connection we tried to retrieve is not available.');
    }
}