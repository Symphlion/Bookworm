<?php

namespace Bookworm;

use \Bookworm\Database;

class Pool {

    /**
     * @var array
     */
    static $connections = [];
    
    /**
     * @var array
     */
    static $tables = [];
    
    /**
     * @var array
     */
    static $queries = [];
    
    /**
     * @brief register a new connection in the drivers class, this way we can 
     * access it everywhere. 
     * @method registerConnection
     * @param string $connection
     * @param array $data
     * @return \Bookworm\Database
     */
    public static function registerConnection( $connection, $data ){
        if( isset(self::$connections[ $connection ])){
            return self::$connections[ $connection ];
        }
        self::$connections[ $connection ] = new Database( $data );
        return self::$connections[ $connection ];
    }
    
    /**
     * @brief retrieve a connection object from the driver store. 
     * @method getConnection
     * @param string $connection
     * @return \Bookworm\Database $driver 
     */
    public static function getConnection( $connection ){
        if( self::$connections[ $connection ]){
            return self::$connections[ $connection ];
        }
        throw new \Exception('The connection we tried to retrieve is not available.');
    }
    
    /**
     * @brief a method to check if the table has already been stored in the pool
     * @method hasTable
     * @public
     * @param string $name or namespace, depending on what you`re looking for
     * @return bool
     */
    public static function hasTable( $name ){
        return in_array($name, array_keys( self::$tables));
    }
    
    public static function registerTable( \Bookworm\Table $table, $id = null ){
        
        if( $id == null ){
            $id = $table->getTableName();
        }
        
        if( ! isset( self::$tables[ $id ]) ){
            self::$tables[ $id ] = $table;
        }
        return self::$tables[ $id ];
    }
    
    
    public function getTable( $name ){
        
    }
    
    public function getTableById( $id ){
        
    }
}