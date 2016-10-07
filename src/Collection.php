<?php

namespace Bookworm;

/**
 * @class Collection
 * @public
 * @package packages\orm
 * @brief Just a generic collection of named routes
 * 
 */
class Collection implements \ArrayAccess, \Countable, \Iterator {

    /**
     * The actual data
     * @property $data
     * @protected
     * @type {array}
     */
    protected $data = [];

    /**
     * @property $index
     * @protected
     * @type {int}
     */
    protected $index = 0;

    /**
     * @property $total
     * @protected
     * @type {int}
     */
    protected $total = 0;

    public function __construct( array $rows = [], $class = null, $is_obj_array = false){
        if( is_array($rows) && count($rows) > 0 && $class !== null ){
            if( ! $is_obj_array){
                $this->addByArray($rows, $class);
            }
            else {
                $this->addObjectsByArray($rows, $class);
            }
        }
    }
    
    /**
     * @brief returns the first element and decreases the collection by 1, 
     * thus removing itself for the collection.
     * @method firset
     * @public
     * @return {\packages\orm\Model}
     */
    public function first() {
        return array_shift($this->data);
    }

    /**
     * @brief After retrieving the data either via get or first, execute this 
     * function as a post event hook.
     * @method posthook
     * @public
     * @return {void}
     */
    public function posthook() {}

    /**
     * Set the associated offset with the given value.
     * 
     * @param {mixed} $key
     * @param {mixed} value
     * @returns {void}
     */
    public function offsetSet($key, $val = null) {
        if ($key instanceof \Bookworm\Model) {
            $this->total++;
            $this->data [] = $key;
        }
        return $this;
    }

    /**
     * @alias offsetSet
     * @see offsetSet
     * @param type $key
     * @param type $value
     */
    public function add($value) {
        $this->offsetSet($value);
        return $this;
    }

    /**
     * @brief add an array of objects to the collection. Make sure you 
     * specify as what kind of models you want them accessed.
     * @method addByArray
     * @public
     * @param {array} $rows
     * @return {packages\orm\Collection}
     */
    public function addByArray($rows, $class) {
        foreach ($rows as $row) {
            $obj = new $class();
            $obj->mergeAttributes($row);
            $this->offsetSet($obj);
        }
        return $this;
    }

    /**
     * @brief add an array of objects to the collection. Make sure you 
     * specify as what kind of models you want them accessed.
     * @method addByArray
     * @public
     * @param {array} $rows
     * @return {packages\orm\Collection}
     */
    public function addObjectsByArray($rows, $class) {
        foreach ($rows as $row) {
            $obj = new $class();
            $obj->mergeAttributesFromObject($row);
            $this->offsetSet($obj);
        }
        return $this;
    }
    
    /**
     * Unset a given value with the given offset.
     * 
     * @param {mixed} offset
     * @returns {void}
     */
    public function offsetUnset($key) {
        unset($this->data[$key]);
    }

    /**
     * @alias offsetUnset
     * @param {mixed} offset
     * @returns {void}
     */
    public function remove($key) {
        $this->offsetUnset($key);
    }

    /**
     * Check if a given offset exists. 
     * 
     * @param {mixed} offset
     * @returns {void}
     */
    public function offsetExists($key, $field = null) {
        if (isset($this->data[$key])) {
            return isset($this->data[$key]);
        } else {
            foreach ($this->data as $item) {
                if ($item->$field == $key) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @alias offsetExists
     * @param type $key
     * @return type
     */
    public function has($key) {
        return $this->offsetExists($key);
    }

    /**
     * Returns the associated value with the given key. The key can be anything
     * obviously, but it does check if the given key exists. 
     * 
     * @param {mixed} offset
     * @returns {mixed|bool} the associated value or false if not found
     */
    public function offsetGet($key) {
        return isset($this->data [$key]) ? $this->data [$key] : false;
    }

    /**
     * Returns a given value based on the key given. 
     * 
     * @param {mixed|string} $key
     * @return {mixed|null} 
     */
    public function get($key = null) {
        if ($key == null) {
            return $this->data;
        }
        return $this->offsetGet($key);
    }

    /**
     * @brief Returns all the elements within the collection as a normal array. 
     * @method all
     * @public
     * @return {mixed|null} 
     */
    public function all() {
        return $this->data;
    }

    /**
     * Overriding the default magical __get behaviour by returning
     * what the default behaviour is for the normal get() function.
     * 
     * @see offsetGet
     * @param {string|mixed} key 
     * @return {mixed}
     */
    public function __get($key) {
        return $this->offsetGet($key);
    }

    /**
     * @brief Returns the number of elements inside the collection object.
     * @method count
     * @public
     * @param {string} $mode
     * @return {int} length
     */
    public function count($mode = 'COUNT_NORMAL') {
        switch ($mode) {
            default: return $this->total;
        }
    }

    /**
     * @brief returns the internal pointer`s Model associated.
     * @method current
     * @public
     * @return {\packages\orm\Model}
     */
    public function current() {
        return $this->data[$this->index];
    }

    /**
     * @brief returns the internal pointer`s index.
     * @method key
     * @public
     * @return {int}
     */
    public function key() {
        return $this->index;
    }

    /**
     * @brief increments the internal pointer by 1. 
     * @method next
     * @public
     * @return {\packages\orm\Collection}
     */
    public function next() {
        $this->index++;
    }

    /**
     * @brief reset the internal pointer to zero. 
     * @method rewind
     * @public
     * @return {\packages\orm\Collection}
     */
    public function rewind() {
        $this->index = 0;
    }

    /**
     * @brief returns true if the current index has a valid value associated
     * @method valid
     * @public
     * @return {bool}
     */
    public function valid() {
        if ($this->index < $this->total) {
            return $this->offsetExists($this->index);
        }
        return false;
    }

}
