<?php

namespace Bookworm;

use Bookworm\Lexicon;

class Query {

    /**
     * @var array
     */
    protected static $_query_methods = [
        ['all', 'where', 'get', 'find'], // static
        ['all', 'where', 'first', 'orwhere', 'get', 'orderby'] // dynamic
    ];

    /**
     * A static array containing all the field information for any given table/class
     * @var array
     */
    protected static $_field_information = [];
    
    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * @var array
     */
    protected $_bindings = [];
    
    /**
     * The query id
     * @var string
     */
    protected $_query_id = null;
    
    /**
     * The unique id
     * @var string
     */
    protected $_unique_id = null;
    
    /**
     * @var bool
     */
    protected $_existing = false;

    /**
     * @brief create a new \Bookworm\Query instance. Since this is the first
     * constructor being called for the object, we`re creating a QueryBuilder
     * for the model.
     * 
     * @method __construct
     * @constructor
     * @public
     */
    public function __construct(){
        $this->_query_id = \Bookworm\Queries::create();
        $this->_unique_id = \Bookworm\Utilities::hash(8, '');
    }
    
    /**
     * @brief a stub you can implement to run before running an update
     * @method beforeUpdate
     * @public
     * @return void
     */
    protected  function beforeUpdate() {}
    
    /**
     * @brief a stub you can implement to run after running an update
     * @method beforeUpdate
     * @public
     * @return void
     */
    protected  function afterUpdate() {}
    
    /**
     * @brief a stub you can implement to run before running an insert
     * @public
     * @return void
     */
    protected function beforeInsert() {}
    
    /**
     * @brief a stub you can implement to run after running an insert
     * @public
     * @return void
     */
    protected function afterInsert() {}
    
    /**
     * @brief a stub you can implement to run before running an delete
     * @public
     * @return void
     */
    protected function beforeDelete() {}
    
    /**
     * @brief a stub you can implement to run after running an delete
     * @public
     * @return void
     */
    protected function afterDelete() {}
    
    
    /**
     * @brief limit the result set by the given $by parameter, optionally, you 
     * can specify the page offset
     * @method limit
     * @param int $by
     * @param int $page
     * @return \Bookworm\Query
     */
    public function limit($by, $page = 0, $abs = false) {
        if (is_array($by) && count($by) == 2) {
            return $this->limit($by[0], $by[1]);
        } else {
            Queries::get($this->_query_id)->limit($by, $page, $abs);
        }
        return $this;
    }

    /**
     * @brief this is where the magic happens :) Here, we start returning
     * data either as a \Bookworm\Model instance of a \Bookworm\Collection instance
     * containing the \Bookworm\Model instances. Also, we clear the builder instance.
     * 
     * @method _get
     * @protected
     * @param int $limit optional also, not required as you can call limit anytime you like, but meh
     * @return \Bookworm\Collection | \Bookworm\Model 
     */
    protected function _get() {
        $limit = Queries::get($this->_query_id)->getLimit();
        // put the query building in process
        $query = Queries::get($this->_query_id)->get();
        // get the aproriate driver for retrieving our query data
        $driver = \Bookworm\Pool::getConnection($this->getConnection());
        // capture the results
        try {
            $results = $driver
                ->query( $query )
                ->bindAssocArray( Queries::get($this->_query_id)->getBindings());
        } catch (Exception $ex) {
            $this->_errors[] = $ex->getMessage();
            $this->_errors[] = "We could not bind the parameters to the query. ";
            return null;
        }
        
        if ( ( $limit && $limit > 1) or $limit === null ) {
            $collection = new \Bookworm\Collection();
            $data = $results->all(\PDO::FETCH_ASSOC);
            $collection->addByArray($data, $this->_table->getClassname());
            $collection->posthook();
            return $collection;
        }
        else if( $limit == 1 ) {
            
            if (!($data = $results->first(\PDO::FETCH_ASSOC))) {
                return null;
            }
            $data = $this->mergeAttributes($data);
            return $data;
        }
    }

    /**
     * @brief returns the first element it finds within a table that has a
     * primary field and it`s associated $id
     * @method _find
     * @param int $id
     * @return \Bookworm\Query
     */
    protected function _find($id) {
        
        Queries::get($this->_query_id)
            ->select('*')
            ->where($this->_table->getPrimaryField(), '=', $id)
            ->limit(1);
        return $this->_get();
    }

    /**
     * @brief returns all the elements it found within the query parameters it
     * was given. Returns a \Bookworm\Collection object with the Query objects
     * within it.
     * @method _all
     * @return \Bookworm\Collection
     */
    protected function _all( $limit = 250, $page = null) {
        $this->limit( $limit, $page, true);
        return $this->_get();
    }
    
    /**
     * @brief returns the first element found within the specified parameters it 
     * was given. Returns a \Bookworm\Model object with the Query objects
     * within it.
     * @method _first
     * @return \Bookworm\Model
     */
    protected function _first(){
        Queries::get( $this->_query_id )
                ->limit(1);
        return $this->_get();
    }
    
    /**
     * @brief Perform a where operation on the query you want, give the field, the
     * operand and the value. If you want to group your where clauses, just pass
     * along the same data in a 2D array. If you want a given operation to be 
     * OR instead of AND, make sure the 4th argument is false. I.e. true is AND
     * false is OR. 
     * @method _where
     * @param string $field
     * @param string $operator
     * @param string $argument
     * @param mixed $optional this field is completely optional, however you can use
     * it as a field operator for specific types of where clauses, like the LIKe clause
     * to specify what kind of LIKE wrapping you`d like for example.
     * @return \Bookworm\Query
     */
    protected function _where($field, $operator, $argument, $optional = null) {
        if( is_string($operator)){
            switch($operator){
                case 'like':
                    Queries::get($this->_query_id)->like($field, $argument, $optional); break;
                case 'notlike': case 'not like':
                    Queries::get($this->_query_id)->notlike($field, $argument, $optional); break;
                case 'between':
                    Queries::get($this->_query_id)->between($field, $argument, $optional); break;
                case 'notbetween': case 'not between':
                    Queries::get($this->_query_id)->notBetween($field, $argument, $optional); break;
                default:
                    Queries::get($this->_query_id)->where($field, $operator, $argument); break;
            }
        } else {
            Queries::get($this->_query_id)->where($field, $operator, $argument);
        }
        return $this;
    }

    /**
     * 
     * @param string|array $field
     * @param string|array $operator
     * @param string $argument
     */
    protected function _orwhere($field, $operator, $argument) {
        Queries::get($this->_query_id)->orwhere($field, $operator, $argument);
        return $this;
    }
    
    /**
     * @brief mostly used for debugging but you can get the retrieve the query ID
     * and see the query builder`s results.
     * @method getQueryId
     * @public
     * @return int
     */
    public function getQueryId(){
        return $this->_query_id;
    }
    
    /**
     * @brief Every Data Object should have a unique ID to identify by. This way, 
     * we can, in a later stage, store all unique Id`s and it`s associating objects
     * in a store where we can retrieve them by ID. 
     * @todo add a model store 
     * @method getUniqueId
     * @public
     * @return int
     */
    public function getUniqueId(){
        return $this->_unique_id;
    }

    /**
     * @brief returns the errors found and stored 
     * @method getErrors
     * @public
     * @return array
     */
    public function getErrors() {
        return $this->_errors;
    }

    /**
     * @brief returns true if there are errors, false if there are none.
     * @method hasErrors
     * @public
     * @return bool
     */
    public function hasErrors() {
        return count($this->_errors) > 0;
    }

    /**
     * @brief set up the fields for this table in the database, retrieves it, 
     * and stores it on the static storage.
     * @method setFields
     * @public
     * @static
     * @param {string} $class
     */
    public static function setFields($class) {
        $object = new $class(true, false);
        $query =  \Bookworm\Lexicon::key('select') . ' * ';
        $query .= \Bookworm\Lexicon::key('from') . '  ' . $object->_table->getTablename() . ' ';
        $query .= \Bookworm\Lexicon::key('limit') . ' 0';

        $stmt = \Bookworm\Pool::getConnection( $object->getConnection())->query($query)->getStatement();

        $stmt->execute();
        $colcount = $stmt->columnCount();

        $columns = [];
        for ($i = 0; $i < $colcount; $i++) {
            $col = $stmt->getColumnMeta($i);
            $columns[] = [
                'name' => $col['name'],
                'length' => $col['len'],
                'type' => $col['native_type'],
                'flags' => $col['flags']
            ];
        }
        self::$_field_information[$class] = $columns;
    }
    
    /**
     * @brief Returns the field data for each column on the table. 
     * @method getFields
     * @public
     * @static
     * @param string $field_data
     */
    public static function getFields() {
        $class = get_called_class();
        if (!isset(self::$_field_information[$class])) {
            self::setFields($class);
        }
        return self::$_field_information[$class];
    } 

    /**
     * @brief call the _ functions of which we can access publicly. 
     * @method __call
     * @public
     * @param string $name
     * @param array|mixed $arguments
     * @return \Bookworm\Query
     */
    public function __call($name, $arguments) {
        if (in_array($name, self::$_query_methods[1])) {
            return call_user_func_array(array($this, '_' . $name), $arguments);
        } else {
            throw new \Exception('A dynamic method has been called, but no implementation was found. The method called: <b>' . __CLASS__ . '::' . $name . '()</b> on line ' . __LINE__, __LINE__);
        }
    }

    /**
     * @brief the __calLStatic is an abstraction to allow certain methods to be called
     * static as well as normal. 
     * @param string $name
     * @param array|mixed  $arguments
     * @return \Bookworm\Query
     */
    public static function __callStatic($name, $arguments) {
        if (in_array($name, self::$_query_methods[0])) {
            $classname = get_called_class();
            
            $obj = new $classname( false );
            // $obj->setClassname($classname);
            
            return call_user_func_array(array($obj, '_' . $name), $arguments);
        }
    }
}
