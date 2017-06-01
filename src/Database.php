<?php

namespace Bookworm;

use Bookworm\Interfaces\DatabaseInterface;
use \PDO as PDO;

class Database implements DatabaseInterface {

    /**
     * @var string
     */
    private $driver = null;

    /**
     * @var string
     */
    private $hostname = 'localhost';

    /**
     * @var string
     */
    private $database = null;

    /**
     * @var string
     */
    private $dsn = null;

    /**
     * @var PDO|null
     */
    private $pdo = null;

    /**
     * @var bool
     */
    private $valid_connection = false;

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @var array
     */
    private $statements = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * Create a new database instance and connect the database up to the given
     * settings ( if provided )
     * 
     * @constructor
     * @param {array} $config
     * @return {Database} 
     */
    public function __construct(array $config = []) {
        if (count($config) > 0) {
            $this->applySettings($config);
        }
    }

    /**
     * @method applySettings
     * @param {array} $config
     * @return {Database} self
     */
    public function applySettings($config) {

        if (isset($config['hostname']) || isset($config['host'])) {
            $this->hostname = isset($config['hostname']) ? $config['hostname'] : $config['host'];
        }

        if (isset($config['driver']) && isset($config['database'])) {
            $this->setDsn($config['driver'], $config['database'], $this->hostname);
        }

        if ($this->hasValidDsn() && isset($config['username']) && isset($config['password'])) {
            $this->connect($config['username'], $config['password']);
        }
        return $this;
    }

    /**
     * @brief prepare the given query for the database. It will load it up and get
     * the prepared statements ready.
     * @method query
     * @public
     * @param   string                  $query
     * @return  \Bookworm\Database
     * @throws \Exception
     */
    public function query($query) {
        if (!$this->connected()) {
            throw new \Exception("No active connection.");
        }
        $this->statements[$this->index] = $this->pdo->prepare($query);

        if (!$this->statements[$this->index]) {
            throw new \Exception("Could not prepare the query!");
        }
        return $this;
    }

    /**
     * Binding a value / parameter to the statement based on the input query given.
     * @method bind
     * @param   string      $key
     * @param   mixed       $value
     * @param   int         $datatype
     * @param   string      $bindtype - switch between value or param
     * @return  \Bookworm\Database
     */
    public function bind($key, $value, $datatype = null, $bindtype = 'value') {
        if (!isset($this->statements[$this->index])) {
            throw new \Exception("No prepared statement to bind on.");
        }

        if (substr($key, 0, 1) != ':') {
            $key = ':' . $key;
        }
        if (!$datatype) {
            $datatype = $this->_getDatatype($value);
        }
        switch ($bindtype) {
            case 'value':
                $this->statements[$this->index]->bindValue($key, $value, $datatype);
                break;
            case 'param':
                $this->statements[$this->index]->bindParam($key, $value, $datatype);
                break;
        }
        return $this;
    }

    /**
     * Do the binding as an array. Provide a key/value paired array or provide
     * 2 arrays, the first being the keys array and the second the values array. 
     * If you choose to provide 2 arrays, make sure they match up index-wise.
     * 
     * @method bindAsArray
     * @param {array} $keys
     * @param {array|null} $values optional
     * @return {Database} self
     */
    public function bindAsArray(array $keys, $values = null) {
        if (is_array($values) && count($values) == count($keys)) {
            $index = 0;
            foreach ($keys as $key) {
                $this->bind($key, $values[$index]);
                $index++;
            }
        } else {
            foreach ($this->keys as $key => $val) {
                $this->bind($key, $val);
            }
        }
        return $this;
    }

    /**
     * @brief Bind an associative array, where the key is the binding key
     * and it`s value, suprise suprise, it`s value. 
     * 
     * @method bindAssocArray
     * @param {array} $binds
     * @return {Database}
     */
    public function bindAssocArray($binds, $types = null) {

        if ($types !== null) {
            foreach ($binds as $bind => $val) {
                $this->bind($bind, $val, $types[$bind]);
            }
        } else {
            foreach ($binds as $bind => $val) {
                $this->bind($bind, $val);
            }
        }
        return $this;
    }

    /**
     * @brief 
     * @method _getDatatype
     * @protected
     * @param mixed     $val
     * @return mixed|PDO::PARAM_<type>
     */
    protected function _getDatatype($val) {
        switch ($val) {
            case is_null($val):
                return PDO::PARAM_NULL;
            case is_int($val):
                return PDO::PARAM_INT;
            case is_bool($val):
                return PDO::PARAM_BOOL;
            default:
                return PDO::PARAM_STR;
        }
    }

    /**
     * Start a transaction. Make sure to commit and rollback if failed.
     * @method transaction
     * @see \Bookworm\Database::commit() and \Bookworm\Database::rollback()
     * @public
     * @return \Bookworm\Database
     */
    public function begin() {
        $this->pdo->beginTransaction();
        return $this;
    }

    /**
     * @brief execute the transaction you started before. If this method fails however,
     * make sure to call the rollback() method to undo the changes you made after the 
     * begin() method was called.
     * @method commit
     * @see \Bookworm\Database::begin() and \Bookworm\Database::rollback()
     * @public
     * @return bool
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * @brief Rollback the transaction. This method should be used in conjunction 
     * with the begin() and commit() methods. Should the commit() fail, you can 
     * roll back to the previous state before the begin() transaction was called.
     * @method rollback
     * @see \Bookworm\Database::begin() and \Bookworm\Database::commit()
     * @public
     * @return \Bookworm\Database
     */
    public function rollback() {
        $this->pdo->rollBack();
        return $this;
    }

    /**
     * @brief returns the row count from the results
     * @method count
     * @public
     * @return int
     */
    public function count() {
        return $this->statements[$this->index - 1]->rowCount();
    }

    /**
     * @brief execute the prepared statement.
     * @method execute
     * @public
     * @return bool
     */
    public function execute() {
        if (!isset($this->statements[$this->index])) {
            throw new \Exception("No prepared statement ready!");
        }
        $capture = $this->statements[$this->index]->execute();
        $this->index++;
        return $capture;
    }

    /**
     * @brief returns the PDO statement object.
     * @method getStatement
     * @public
     * @return \PDO\PDOStatement
     */
    public function getStatement() {
        return $this->statements[$this->index];
    }

    /**
     * @brief Returns the first element found. However, if no limit clause was added
     * this function might return a larger dataset.. 
     * 
     * @method first
     * @public
     * @param int           $fetch_mode defaults to PDO::FETCH_OBJ
     * @param string|null   $as         optionally cast the results to a new object of this type
     * @return \Object
     */
    public function first($fetch_mode = PDO::FETCH_OBJ, $as = null) {
        try {
            $this->execute();
            return $this->statements[$this->index - 1]->fetch($fetch_mode);
        } catch (Exception $ex) {
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     * @brief Returns all the elements it found while trying to execute the 
     * statement previously prepared.
     * @method all
     * @public
     * @param   int         $fetch_mode     defaults to PDO::FETCH_OBJ
     * @param   string|null $as             optional - cast the results to a new object of this type
     * @return  \Object
     */
    public function all($fetch_mode = PDO::FETCH_OBJ, $as = null) {
        try {
            $this->execute();
            if ($as === null) {
                return $this->statements[$this->index - 1]->fetchAll($fetch_mode);
            } else {
                return $this->statements[$this->index - 1]->fetchAll($fetch_mode, $as);
            }
        } catch (\Exception $ex) {
            $this->errors[] = "We could not execute the statement given.";
        }
    }

    /**
     * Connect with the given username and password.
     * @method connect
     * @public
     * @param string    $username
     * @param string    $password
     * @return \Bookworm\Database
     */
    public function connect($username, $password) {
        try {
            $this->disconnect();
            $options = array(
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            );
            $this->pdo = new PDO($this->getDsn(), $username, $password, $options);
            $this->valid_connection = true;
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage();
        }
        return $this;
    }

    /**
     * Clear the connection.
     * @method close
     * @public
     * @return {Database} self
     */
    public function disconnect() {
        $this->pdo = null;
        return $this;
    }

    /**
     * @brief set up the DSN connection string
     * @method setDsn
     * @public
     * @param   string  $driver
     * @param   string  $database
     * @param   string  $hostname
     * @return  \Bookworm\Database
     */
    public function setDsn($driver, $database, $hostname) {
        $this->driver = $driver;
        $this->database = $database;
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * @brief A helper to see if we have a valid connection.
     * @method isConnected
     * @public
     * @return bool
     */
    public function connected() {
        return $this->valid_connection;
    }

    /**
     * @brief A way to check if all the fields required to create a dsn sting are present.
     * @method hasValidDsn
     * @public
     * @return bool
     */
    public function hasValidDsn() {
        return ($this->driver && $this->database && $this->hostname);
    }

    /**
     * @brief Returns the dsn string, but first checks if all the values required for 
     * the dsn string are present. 
     * @method getDsn
     * @public
     * @return string|null
     */
    public function getDsn($override = false) {
        if ($this->dsn && !$override) {
            return $this->dsn;
        }
        if ($this->hasValidDsn()) {
            $this->dsn = $this->driver . ':dbname=' . $this->database . ';host=' . $this->hostname;
            return $this->dsn;
        }
        return null;
    }

    /**
     * @brief Returns the if of the last inserted record.
     * @method lastId
     * @public
     * @return int result
     */
    public function getLastId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * @brief a way to see if the database encountered errors, if it has, this method
     * will return true. 
     * @method hasErrors
     * @public
     * @return bool
     */
    public function hasErrors() {
        return count($this->errors) > 0;
    }

    /**
     * @brief returns all the errors the database encountered.
     * @method getErrors
     * @public
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * @brief add a error message.
     * @method error
     * @public
     * @param string    $key
     * @param string    $msg
     * @return \Bookworm\Database
     */
    public function error($key, $msg = null) {

        if ($msg === null) {
            $msg = $key;
            $key = count($this->errors);
        }

        $this->errors[$key] = $msg;
        return $this;
    }

}
