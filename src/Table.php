<?php

namespace Bookworm;

use \ICanBoogie\Inflector;

class Table {
 
    /**
     * @property $name
     * @protected
     * @var string|null
     */
    private $table_name;
    
    /**
     * The iso code of the languiage the models are named for. 
     * @var string
     */
    private $language = 'en';
    
    /**
     * @property $primary_field
     * @protected
     * @var string
     * @defaults 'id'
     */
    protected $primary_field = 'id';
    
    /**
     * @var string|null
     */
    protected $classname = null;
    
    public function __construct( $classname = null, $language = 'en' ){
        if( $classname !== null ){
            $this->table_name = $classname;
            // $this->table_name = Table::resolveInflectedTablename( $classname );
        }
        $this->classname = $classname;
        
        if( $language === null ){
            $language = 'en';
        }
        $this->language = $language;
        
        // override the primary field for this table as apparently, you can be a
        // dick, and leave it as an empty string.
        if( strlen( $this->primary_field) == 0 or $this->primary_field == null ){
            $this->primary_field = 'id';
        }
    }
    
    /**
     * @brief set the table name. 
     * @method setTableName
     * @public
     * @param {string} $table 
     * @return {void}
     */
    public function setTableName( $table ){
        if( !is_string($table)){ return; }
        $this->table_name = $table;
    }
    
    /**
     * @brief returns the name of the table.
     * @method getTableName
     * @public
     * @return {string} $table
     */
    public function getTableName(){
        return $this->table_name;
    }
    
    /**
     * @brief set the primary field for the table. The primary field defaults
     * to the string 'id' which in most cases hsould be sufficient.
     * @method setPrimaryField
     * @public
     * @param {string} $primary_field 
     * @return {void}
     */
    public function setPrimaryField ( $primary_field ){
        if( !is_string($primary_field)){ return; }
        $this->primary_field = $primary_field;
    }
    
    /**
     * @brief returns the primary field for the table. Can be specified, but defaults
     * to the string 'id' which in most cases hsould be sufficient.
     * @method getPrimaryField
     * @public
     * @return string
     */
    public function getPrimaryField(){
        return $this->primary_field;
    }
    
    /**
     * @brief a way to set up the classname if the classname is different
     * from the table`s name, we still need to be able to invoke the right
     * model.
     * @method setClassname
     * @public
     * @param string $classname
     */
    public function setClassname( $classname ){
        $this->classname = $classname;
    }
    
    /**
     * @brief returns the classname used to establish the tablename.
     * @method getClassname
     * @public
     * @return string
     */
    public function getClassname(){
        return $this->classname;
    }
    
    /**
     * @brief getSingular
     * @method getSingular
     * @public
     * @param string $lang - optional
     * @return string
     */
    public function getSingular( $lang = 'en'){
        return Inflector::get($lang)->singularize( $this->table_name);
    }
    
    /**
     * @brief The input string will be transformed the same way we`d resolve the
     * normal table name based on the class given.
     * @method resolveTablename
     * @public
     * @param {string} $input 
     * @return {string} $output
     */
    public static function resolveInflectedTablename( $namespaceClass, $lang = 'en' ){
        if( !is_string($lang)){
            return null;
        }
        $class = self::resolveClassname($namespaceClass);
        return Inflector::get($lang)->pluralize( $class );
    }
    
    /**
     * @brief The input string will be transformed the same way we`d resolve the
     * normal table name based on the class given.
     * @method resolveSingleName
     * @public
     * @param {string} $input 
     * @return {string} $output
     */
    public static function resolveClassname( $namespaceClass ){
        $args = explode( "\\", $namespaceClass );
        return strtolower(end($args));
    }
 
    public static function r( $id ){
        return \Bookworm\Pool::getTable( $id );
    }
}