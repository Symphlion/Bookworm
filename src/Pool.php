<?php

namespace Bookworm;

use \Bookworm\Database;
use \Bookworm\Interfaces\DatabaseInterface;

class Pool {

    /**
     * @var array
     */
    protected static $connections = [];
    
    /**
     * @var array
     */
    protected static $tables = [];
    
    /**
     * @var array
     */
    public static $queries = [];
    
    /**
     * @brief add a new connection in the drivers class, this way we can 
     * access it everywhere. 
     * @method createConnection
     * @param string $connection
     * @param array $data
     * @return \Bookworm\Database
     */
    public static function createConnection ( $connection, $data ){
        if( isset(self::$connections[ $connection ])){
            return self::$connections[ $connection ];
        }
        
        if( is_array( $data )){
            self::$connections[ $connection ] = new Database( $data );
        }
        else if ( $data instanceof DatabaseInterface ){
            self::$connections[ $connection ] = $data;
        }
        
        return self::$connections[ $connection ];
    }
    
    
    /**
     * @brief create a new Query Builder instance, add it to the queries class
     * and return the id. 
     * @method createQuery
     * @public
     * @static
     * @return string
     */
    public static function createQuery (){
        $id = \Bookworm\Utilities::inthash(4, '');
        self::$queries[$id] = new \Bookworm\Builder( $id );
        return $id;
    }
    
    /**
     * @brief create a \Bookworm\Table to the Pool.
     * @method createTable
     * @public
     * @param   \Bookworm\Table     $table
     * @param   string|int|mixed    $id
     * @return  string
     */
    public static function createTable( \Bookworm\Table $table, $id = null ){
        if( $id == null ){
            $id = $table->getTableName();
        }
        if( ! isset( self::$tables[ $id ]) ){
            self::$tables[ $id ] = $table;
        }
        return $id;
    }
    
    /**
     * @brief retrieve a connection object from the driver store. 
     * @method getConnection
     * @param string $connection
     * @return \Bookworm\Database $driver 
     */
    public static function getConnection( $connection ){
        if( self::hasConnection($connection)){
            return self::$connections[ $connection ];
        }
        throw new \Exception('The connection we tried to retrieve is not available.');
    }
    
    /**
     * @brief returns the associated table with the given ID
     * @method getTableById
     * @public
     * @param   string|int|mixed    $id
     * @returns \Bookworm\Table|null
     */
    public static function getTable( $id ){
        if( isset(self::$tables[ $id ])){
            return self::$tables[ $id ];
        }
        return null;
    }
    
    /**
     * @brief a helper method to retrieve the Query Builder instance registered 
     * previously. If, for some reason the ID given has no associated Builder,
     * we have an automatic fail-over and create a new Builder instance and return
     * that one instead.
     * @method get
     * @static
     * @public
     * @param string    $id
     * @param bool      $copy_builder
     * @return \Bookworm\Builder
     */
    public static function getQuery ($id, $copy_builder = true){
        if( !self::$queries[$id]){
            $id = \Bookworm\Utilities::inthash(4);
            self::$queries[ $id ] = new \Bookworm\Builder( $id , $copy_builder);
        }
        return self::$queries[ $id ];
    }
    
    
    
    /**
     * @brief a method to check if there is a connection with the given name.
     * @method  hasConnection
     * @public
     * @param   string $connection
     * @return  bool
     */
    public static function hasConnection( $connection ){
        return isset( self::$connections[ $connection ] );
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
    
    /**
     * @brief a helper to check if the given ID exists and has a \Bookworm\Builder
     * associated with it.
     * @method hasQuery
     * @public
     * @param string $id 
     * @return bool
     */
    public static function hasQuery ( $id ){
        return isset( self::$connections[ $id ] );
    }
    
    /**
     * @brief iterate over the tables we stored and return the \Bookworm\Table
     * where the table name matches the given $name.
     * @method getTableByName
     * @public
     * @param   string              $name
     * @return  \Bookworm\Table|null
     */
    public static function getTableByName( $name ){
        foreach(self::$tables as $table ){
            if( $table->getTablename() === $name ){
                return $table;
            }
        }
        return null;
    }
    
}