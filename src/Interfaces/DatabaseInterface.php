<?php

namespace Bookworm\Interfaces;

/**
 * @access public
 * @abstract
 * @category Interfaces
 */
interface DatabaseInterface {
    
    
    public function query( $query );
    
    public function bind( $key, $value, $datatype = null, $bindtype = 'value');
 
    /**
     * @brief this method should return all the dataset it found, although you could
     * ( and probably should ) implement a default limit so as not to crash your 
     * storage.
     * @method all
     * @public
     * @return \Bookworm\Collection
     */
    public function all( $as );
    
    /**
     * @brief this function should return the first dataset it found within the
     * given query or scope. 
     * @method first
     * @public
     * @return \Bookworm\Model
     */
    public function first( $as );
    
    /**
     * @brief the default action to start up the connection. 
     * @method connect
     * @public
     * @param   string  $username
     * @param   string  $password
     * @return \Bookworm\Interfaces\DatabaseInterface
     */
    public function connect( $username, $password );
    
    /**
     * @brief a method to check if there is a working connection to begin with.
     * @method connected
     * @public
     * @return  bool
     */
    public function connected();
    
    /**
     * @brief a method to disconnect the current connection.
     * @method diconnect
     * @public
     * @return  bool
     */
    public function disconnect();
    
    /**
     * @brief this should be the way to retrieve all the errors your implementation
     * finds. 
     * @method getErrors
     * @public
     * @return  array
     */
    public function getErrors();
    
    /**
     * @brief store a error message. If the $msg is empty or null, $key will become
     * $msg.
     * @method error
     * @public
     * @param   string $key
     * @param   string $msg
     */
    public function error( $key, $msg = null );
}