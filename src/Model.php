<?php

namespace Bookworm;

use Bookworm\Table;
use Bookworm\Query;

class Model extends Relation {

    protected $connection = null;
    protected $primaryfield = null;
    protected $table = null;

    /**
     * @var \Bookworm\Table
     */
    protected $_table = null;

    /**
     * @var array
     */
    protected $_modified_attributes = [];

    /**
     * @var array
     */
    protected $_stored_attributes = [];

    /**
     * @property a flag to check if there has been a merge of attributes
     * @var bool
     */
    protected $_has_merge = false;

    /**
     * @var int|mixed
     */
    protected $_id = null;

    public function __construct($is_new_object = true, $copy_builder = true) {
        parent::__construct();
        $classname = get_called_class();

        if ($this->table !== null) {
            $this->_table = new \Bookworm\Table($this->table);
        } else {
            $this->_table = new \Bookworm\Table($classname);
            $this->table = $this->_table->getTableName();
        }

        if ($this->primaryfield !== null) {
            $this->_table->setPrimaryField($this->primaryfield);
        }

        // if this was a new object, we`re going to try and do an insert.
        if ($is_new_object) {
            $this->_is_new_object = $is_new_object;
            Queries::get($this->_query_id, $copy_builder)->insert($this->_table->getTablename());
        } else {
            // the initial select query as we`re most likely going to retrieve data
            Queries::get($this->_query_id, $copy_builder)->select('*')->from($this->_table->getTableName());
        }
    }

    /**
     * @brief save the model to the storage driver. If it happens to be an existing
     * object, it will perform an update query, if there was no existing object, 
     * it will perform an insert query.
     * @method save
     * @public
     * @return bool
     * @throws \Exception
     */
    public function save() {
        // we`re doing an insert
        $is_new_object = false;
        $query = false;

        if ($this->isExistingRow()) {
            $query = $this->createUpdateQuery();
        }
        if ($this->isNewRow()) {
            $is_new_object = true;
            $query = $this->createInsertQuery();
        }

        if ($query) {
            try {
                $driver = \Bookworm\Pool::getConnection($this->getConnection());
                
                $result = $driver
                        ->query($query)
                        ->bindAssocArray(Queries::get($this->_query_id)->getBindings())
                        ->execute();

                if ($is_new_object) {
                    $this->_id = $driver->getLastId();
                    $this->_stored_attributes = $this->_modified_attributes;
                }

                $this->_binds = [];
                return $result;
                
            } catch (\Exception $r) {
                $this->_errors[] = $r->getMessage();
                $this->_errors[] = "We could not save the object data to the storage driver.";
            }
        } else {
            if($this->isExistingRow()){
                $this->_errors[] = "Tried to perform an UPDATE, however, there is no modified data to store!";
            }
        }
        return false;
    }
    
    /**
     * @brief revert the modified data back to the original data and update ( or
     * revert ) it back to the state is was in before.
     * @method revert
     * @public
     * @return bool
     */
    public function revert($keep_origin = false ){
        
        $original = $this->_stored_attributes;
        $modified = $this->_modified_attributes;
        
        $this->_modified_attributes = $this->_stored_attributes;
        $this->_stored_attributes = $modified;
        
        $status = $this->save();
        
        if( $status === true && $keep_origin === true ){
            $this->_stored_attributes = $original;
        } 
        
        if( $status === false ){
            $this->_stored_attributes = $original;
            $this->_modified_attributes = $modified;
        }
        
        return $status;
    }
    
    /**
     * @brief Update an existing row in the database. Iterate over the fields 
     * we have and store it .
     * @method _update
     * @protected
     * @return {bool}
     */
    protected function createInsertQuery() {
        $builder = Queries::get($this->_query_id)
                ->insert($this->_table->getTableName());


        $set = [];

        foreach ($this->getFields() as $field) {
            $is_required = false;
            $skip = false;
            // check if the field is our primary key
            if (\Bookworm\Utilities::hasFlag($field['flags'], 'primary_key')) {
                $this->_table->setPrimaryField($field['name']);
                continue;
            }
            // check if the field requires a value to be set
            else if (\Bookworm\Utilities::hasFlag($field['flags'], 'not_null')) {
                $is_required = true;
            }

            $val = $this->getAttributeValue($field, $is_required);

            if ($val !== null) {
                // $fields[] = $field['name'];
                $set[$field['name']] = $val;
            } else {
                if ((empty($val) or ( !$val && $val !== false)) && $is_required) {
                    // we have an error.. we need that field!
                    $this->_errors[] = 'Required field `' . $field['name'] . '` is empty. The type is ' . $field['type'] . '.';
                }
            }
        }
        if (count($set) > 0 && !$this->hasErrors()) {
            return $builder
                            ->fieldnames(array_keys($set))
                            ->values($set)
                            ->get();
        }
        return false;
    }

    /**
     * @brief Update an existing row in the database. Iterate over the fields 
     * we have and store it.
     * @method update
     * @protected
     * @return string
     */
    protected function createUpdateQuery() {

        $builder = Queries::get($this->_query_id)
                ->reset()
                ->update($this->_table->getTableName())
                ->where($this->_table->getPrimaryField(), '=', $this->getId());

        foreach ($this->getFields() as $field) {

            if (\Bookworm\Utilities::hasFlag($field['flags'], 'primary_key')) {
                $val = null;
            } else if ($field['name'] == 'updated_at') {
                $val = null;
            } else if ($field['name'] == 'created_at') {
                $val = null;
            } else if (isset($this->_stored_attributes[$field['name']]) && $this->_stored_attributes[$field['name']] == $this->_modified_attributes[$field['name']]) {
                $val = null;
            } else if ($this->_modified_attributes[$field['name']] == null) {
                $val = null;
            } else {
                $val = $this->_modified_attributes[$field['name']];
            }

            if ($val !== null) {
                $builder->set($field['name'], $val);
            }
        }

        return $builder->get();
    }

    /**
     * @brief returns the value for a given attirbute field as defined by the database
     * table schema. 
     * @method _getAttributeValue
     * @protected
     * @param {array} $field
     * @return {mixed}
     */
    protected function getAttributeValue($field, $is_required = false) {
        if (isset($this->_modified_attributes[$field['name']])) {
            return $this->_modified_attributes[$field['name']];
        }
        // if (self::hasFlag($field['flags'], 'not_null')) {
        if ($is_required) {
            if ($field['type'] == 'TIMESTAMP') {
                return date('Y-m-d H:i:s');
            }
            if ($field['type'] == 'DATETIME') {
                return date('Y-m-d H:i:s');
            }
            return null;
        } else {
            return \Bookworm\Utilities::getDefaultValue($field['type']);
        }
    }

    /**
     * @brief returns true if this is an existing record.
     * @method isExistingRow
     * @public
     * @return {bool}
     */
    public function isExistingRow() {
        return ( count($this->_stored_attributes) >= 1 );
    }

    /**
     * @brief returns true if this is a new record.
     * @method isNewRow
     * @public
     * @return boolean
     */
    public function isNewRow() {
        return !$this->isExistingRow();
    }

    /**
     * @brief the unique identifier for this object. By default is the normal 
     * increcementing primary key from any given table.
     * @method getId
     * @public
     * @return int|mixed
     */
    public function getId() {
        return $this->_id;
    }

    public function getTable(){
        return $this->_table;
    }
    
    /**
     * @brief set up the connection for this model by passing it as a string
     * to the function.
     * 
     * @method setConnection
     * @public
     * @param string $connection
     * @return \Bookworm\Model
     */
    public function setConnection($connection) {
        if (is_string($connection)) {
            $this->connection = $connection;
        }
        return $this;
    }

    /**
     * @brief returns the connection string used for the model 
     * @method getConnection
     * @public
     * @return string
     */
    public function getConnection() {
        if ($this->connection !== null) {
            return $this->connection;
        }
        return 'default';
    }

    /**
     * @brief returns the data from the live or modified fields otherwise, falls
     * back to the stored attributes fields, if those don`t exist, returns null.
     * @method __get
     * @public
     * @param   string $name
     * @return  mixed|string|null
     */
    public function __get($name) {
        if (isset($this->_modified_attributes[$name])) {
            return $this->_modified_attributes[$name];
        }
        if (isset($this->_stored_attributes[$name])) {
            return $this->_stored_attributes[$name];
        }
        
        // as a last resort, checks if we have a method we can call
        if(method_exists($this, $name)){
            return $this->$name();
        }
        return null;
    }

    /**
     * @brief set a new value for the attributes. 
     * @method __set
     * @public
     * @param   string $key
     * q@param   string $value
     * @return  mixed|string|null
     */
    public function __set($key, $value) {
        return $this->_modified_attributes[$key] = $value;
    }

    /**
     * @brief merge the data fields from storage with the attributes array
     * @method mergeAtributes
     * @public
     * @param   array   $data
     * @return  \Bookworm\Model
     */
    public function mergeAttributes($data) {
        if (!$this->_has_merge) {
            $this->_has_merge = true;
            if (is_array($data)) {
                if (isset($data[$this->_table->getPrimaryField()])) {
                    $this->_id = $data[$this->_table->getPrimaryField()];
                } else {
                    // naively assume the ID will be "id" 
                    if (isset($data['id'])) {
                        $this->_id = $data['id'];
                    }
                }
                $this->_modified_attributes = $data;
                $this->_stored_attributes = $data;
            }
        }
        return $this;
    }

    /**
     * @brief merge the data fields from storage with the attributes object
     * @method mergeAtributes
     * @public
     * @param Object $data
     * @return \Bookworm\Model
     */
    public function mergeAttributesFromObject($obj) {
        if (!$this->_has_merge) {
            $this->_has_merge = true;
            if (is_object($obj)) {
                if ($obj->{$this->_table->getPrimaryField()}) {
                    $this->_id = $obj->{$this->_table->getPrimaryField()};
                } else {
                    // naively assume the ID will be "id" 
                    if ($obj->id) {
                        $this->_id = $obj->id;
                    }
                }
                $data = [];
                foreach ($obj as $key => $val) {
                    $data[$key] = $val;
                }

                $this->_modified_attributes = $data;
                $this->_stored_attributes = $data;
            }
        }
        return $this;
    }

}
