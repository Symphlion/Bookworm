<?php


namespace Bookworm;

class Queries {

    public static $queries = [];
    
    /**
     * @brief create a new Query Builder instance, register it to the queries class
     * and return the id. 
     * @method create
     * @public
     * @static
     * @return string
     */
    public static function create(){
        $id = \Bookworm\Utilities::inthash(4, '');
        self::$queries[$id] = new \Bookworm\Builder( $id );
        return $id;
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
    public static function get($id, $copy_builder = true){
        if( !self::$queries[$id]){
            $id = \Bookworm\Utilities::inthash(4);
            self::$queries[ $id ] = new \Bookworm\Builder( $id , $copy_builder);
        }
        return self::$queries[ $id ];
    }
    
}