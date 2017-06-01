<?php

namespace Bookworm;

use \Bookworm\Lexicon;
use \Bookworm\Utilities;

class Builder {

    /**
     * @property The final query we`re producing after we call the get() method
     * @var type 
     */
    protected $query = null;

    /**
     * @property All the bounded properties and associated values
     * @var array
     */
    protected $bindings = [];

    /**
     * @property All the bounded properties and associated types
     * @var array
     */
    protected $bindingTypes = [];

    /**
     * @property a flag to see if we called either select/update/delete/insert
     * @var bool
     */
    private $initialized = false;

    /**
     * @property if a limit has been set ( most cases will ) this will return the 
     * last given limit. 
     * @var int
     */
    private $limit = 0;

    /**
     * @property all the different clauses we`re using this time around.
     * @var array
     */
    protected $clauses = [];

    /**
     * @property The template for the clauses array which we can use to reset the builder
     * instance. 
     * @var array
     */
    protected static $clauses_template = [
        'select' => [],
        'update' => null,
        'insert' => null,
        'delete' => null,
        'limit' => null,
        'from' => [],
        'set' => [],
        'values' => [],
        'fieldnames' => [],
        'innerjoin' => [],
        'rightjoin' => [],
        'crossjoin' => [],
        'leftjoin' => [],
        'orderby' => [],
        'groupby' => [],
        'where' => [],
        'orwhere' => [],
        'between' => [],
        'notbetween' => [],
        'orbetween' => [],
        'ornotbetween' => [],
        'like' => [],
        'orlike' => [],
        'notlike' => [],
        'ornotlike' => [],
        'having' => [],
        'orhaving' => []
    ];

    /**
     * @property Capture some rudimentary stats to check if we need to execute them.
     * @var array
     */
    protected $statistics = [
        'joins' => 0,
        'where' => 0,
        'between' => 0,
        'like' => 0,
        'having' => 0
    ];

    /**
     * @property The unique ID for the current \Bookworm\Builder instance.
     * @var string
     */
    protected $query_id = null;

    /*
     * The instantion of the object and query ID assignment and retrieval
     */

    /**
     * @brief instantiates a new \Bookworm\Builder instance. If an ID is given,
     * we`ll use that ID  as the unique identifier for this instance. The second
     * parameter should default to true, so that we have a working clauses array
     * to store our data in.
     *  
     * @param int|string    $id
     * @param bool          $copy
     */
    public function __construct($id = null, $copy = true) {
        if ($id !== null) {
            $this->query_id = $id;
        }

        if ($copy) {
            $this->clauses = \Bookworm\Builder::$clauses_template;
        }
    }

    /**
     * @brief return the Query ID for this instance.
     * @method getQueryId
     * @public
     * @return string
     */
    public function getQueryId() {
        return $this->query_id;
    }

    /**
     * @brief set up the Query ID for this instance. You should only add one if
     * no query ID has been set or assignment already took place.
     * @method setQueryId
     * @public
     * @param string $query_id
     * @return \Bookworm\Builder
     */
    public function setQueryId($query_id) {
        if ($query_id !== null) {
            $this->query_id = $query_id;
        }
        return $this;
    }

    /**
     * @brief create a new instance from a static reference point. 
     * @method create
     * @static
     * @return \Bookworm\Builder
     */
    public static function create() {
        return new self();
    }

    /**
     * @brief add a `SELECT` clause to the query 
     * @method select
     * @public
     * @param string $table
     * @param [, string] $table - if you add more then 1 table, it will recursively
     * call this method to add all the tables in the function arguments array. 
     * @return \Bookworm\Builder
     */
    public function select($field) {
        if (!$this->initialized) {
            $this->initialized = true;
            if (func_num_args() > 1) {
                foreach (func_get_args() as $field) {
                    $this->select($field);
                }
            } else {
                $this->clauses['select'][] = $field;
            }
        }
        return $this;
    }

    /**
     * @brief Add a second ( or third etc) field in your select clause.
     * @method addSelect
     * @public
     * @param string $field
     * @return \Bookworm\Builder
     */
    public function addSelect($field) {
        if (func_num_args() > 1) {
            foreach (func_get_args() as $field) {
                $this->addSelect($field);
            }
        } else {
            $this->clauses['select'][] = $field;
        }
        return $this;
    }

    /**
     * @brief add a `UPDATE` clause to the query
     * @method update
     * @public
     * @param string $table
     * @return \Bookworm\Builder
     */
    public function update($table) {
        if (!$this->initialized) {
            $this->initialized = true;
            $this->clauses['update'] = $table;
        }
        return $this;
    }

    /**
     * @brief add a `INSERT INTO` clause to the query
     * @method insert
     * @public
     * @param string $table
     * @return \Bookworm\Builder
     */
    public function insert($table) {
        if (!$this->initialized) {
            $this->initialized = true;
            $this->clauses['insert'] = $table;
        }
        return $this;
    }

    /**
     * @brief add a `DELETE` clause to the query
     * @method delete
     * @public
     * @param string $table
     * @return \Bookworm\Builder
     */
    public function delete($table) {
        if (!$this->initialized) {
            $this->initialized = true;
            $this->clauses['delete'] = $table;
        }
        return $this;
    }

    /**
     * @brief add a `FROM` clause to select from. You can specify as many tables
     * as you like however.
     * @example 
     *      $builder->from("users");
     *      $builder->from("users u", "preferences p");
     * @method from
     * @public
     * @param string $table
     * @param string [, string] $table - if you add more then 1 table, it will recursively
     * call this method to add all the tables in the function arguments array. 
     * @return \Bookworm\Builder
     */
    public function from($table) {
        if (func_num_args() > 1) {
            foreach (func_get_args() as $table) {
                $this->from($table);
            }
        } else {
            $this->clauses['from'][] = $this->splitwrap($table, ' ');
        }
        return $this;
    }

    /**
     * @brief add a array of fields for `SET`ting and updating a row 
     * @method set
     * @public
     * @param array $field
     * @return \Bookworm\Builder
     */
    public function set($key, $value = null) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            if (is_string($key) && $value !== null) {
                $this->clauses['set'][] = $this->wrap($key) . ' = ' . $this->createBinding($value);
            }
        }
        return $this;
    }

    /**
     * @brief add a array of fields you want to set up in your INSERT clause. 
     * @method fieldnames
     * @public
     * @param array $fields
     * @return \Bookworm\Builder
     */
    public function fieldnames($fields) {
        $this->clauses['fieldnames'] = array_unique(
                array_merge($this->clauses['fieldnames'], $fields)
        );

        return $this;
    }

    /**
     * @brief add an array of values for your INSERT query. 
     * @method values
     * @public
     * @param array $fields
     * @param array $types - optional
     */
    public function values($fields, $types = null) {
        if (is_array($fields)) {
            $binds = [];
            if ($types !== null) {
                foreach ($fields as $k => $value) {
                    $binds[] = $this->createBinding($value, $types[$k]);
                }
            } else {
                foreach ($fields as $value) {
                    $binds[] = $this->createBinding($value);
                }
            }
            $this->clauses['values'][] = ' (' . implode(', ', $binds) . ') ';
        }
        return $this;
    }

    /**
     * Joining tables, either inner join, left/right join or cross join
     */

    /**
     * @brief add a normal inner join to the query.
     * @method join
     * @example 
     *      $builder->join('users', 'users.id', 'preferences.user_id');
     * @public
     * @param string $table
     * @param string $field
     * @param string $from
     * @return \Bookworm\Builder
     */
    public function join($table, $field, $from) {
        $this->statistics['joins'] ++;
        $join = \Bookworm\Lexicon::key('innerjoin') . ' ' . $this->splitwrap($table, ' ')
                . ' ' . \Bookworm\Lexicon::key('on') . ' ' . $this->splitwrap($from)
                . ' = ' . $this->splitwrap($field);
        $this->clauses['innerjoin'][] = $join;
        return $this;
    }

    /**
     * @brief add a left join to the query.
     * @method leftjoin
     * @example 
     *      $builder->leftjoin('users', 'users.id', 'preferences.user_id');
     * @public
     * @param string $table
     * @param string $field
     * @param string $from
     * @return \Bookworm\Builder
     */
    public function leftjoin($table, $field, $from) {
        $this->statistics['joins'] ++;
        $join = \Bookworm\Lexicon::key('leftjoin') . ' ' . $this->wrap($table)
                . ' ' . \Bookworm\Lexicon::key('on') . ' ' . $this->wrap($field)
                . ' = ' . $this->wrap($from);
        $this->clauses['leftjoin'][] = $join;
        return $this;
    }

    /**
     * @brief add a right join to the query.
     * @method rightjoin
     * @example 
     *      $builder->rightjoin('users', 'users.id', 'preferences.user_id');
     * @public
     * @param string $table
     * @param string $field
     * @param string $from
     * @return \Bookworm\Builder
     */
    public function rightjoin($table, $field, $from) {
        $this->statistics['joins'] ++;
        $join = \Bookworm\Lexicon::key('rightjoin') . ' ' . $this->wrap($table)
                . ' ' . \Bookworm\Lexicon::key('on') . ' ' . $this->wrap($field)
                . ' = ' . $this->wrap($from);
        $this->clauses['rightjoin'][] = $join;
        return $this;
    }

    /**
     * Narrowing down the query results
     */

    /**
     * @brief Add a `WHERE` clause which, by default is a normal AND clause.
     * @method where
     * @public
     * @param string|array  $field
     * @param string        $equals
     * @param string|array  $value
     * @param bool          $return optional, if true returns the `WHERE` as a string, defaults to false
     * @return \Bookworm\Builder|string
     */
    public function where($field, $equals, $value, $return = false) {
        $this->statistics['where'] ++;
        if ($this->isWhereArray($field) && $this->isWhereArray($value)) {
            $logical = \Bookworm\Lexicon::validate($equals, 'logical');
            $first_where = $this->extractWhereClause($field);
            $second_where = $this->extractWhereClause($value);
            $this->clauses['where'][] = '(' . $first_where . ' ' . $logical
                    . ' ' . $second_where . ')';
        } else {

            $equals = Lexicon::validate($equals, 'equality');
            $where_clause = $this->splitwrap($field) . ' ' . $equals . ' '
                    . $this->createBinding($value);

            if ($return) {
                return $where_clause;
            }
            $this->clauses['where'][] = $where_clause;
        }
        return $this;
    }

    /**
     * @brief Add a `OR WHERE` clause which, by default is a normal OR clause.
     * @method orWhere
     * @public
     * @param string|array  $field
     * @param string        $equals
     * @param string|array  $value
     * @param bool          $return - optional, if true returns the `OR WHERE` as a string, defaults to false
     * @return \Bookworm\Builder|string
     */
    public function orWhere($field, $equals, $value, $return = false) {
        $this->statistics['where'] ++;
        if ($this->isWhereArray($field) && $this->isWhereArray($value)) {

            $logical = (in_array($equals, \Bookworm\Lexicon::$allowed['logical'])) ? $equals : \Bookworm\Lexicon::$allowed['logical']['default'];

            $this->clauses['orwhere'][] = '(' . $this->where($field[0], $field[1], $field[2], true)
                    . ' ' . $logical . ' '
                    . $this->where($value[0], $value[1], $value[2], true) . ')';
        } else {
            $equals = (in_array($equals, \Bookworm\Lexicon::$allowed['equality'])) ? $equals : \Bookworm\Lexicon::$allowed['equality']['default'];

            $where_clause = $this->wrap($field) . ' ' . $equals
                    . ' ' . $this->createBinding($value);
            if ($return) {
                return $where_clause;
            }
            $this->clauses['orwhere'][] = $where_clause;
        }
        return $this;
    }

    /**
     * @brief add a 'BETWEEN' clause to the query.
     * @method between
     * @public
     * @param string $field
     * @param string|mixed $begin
     * @param string|mixed $end
     */
    public function between($field, $begin, $end, $return = false) {
        $this->statistics['between'] ++;
        $between = $this->wrap($field) . ' ' . \Bookworm\Lexicon::key('between')
                . ' ' . $this->toNumber($begin) . ' '
                . \Bookworm\Lexicon::key('and') . ' ' . $this->toNumber($end);
        if ($return) {
            return $between;
        }
        $this->clauses['between'][] = $between;
        return $this;
    }

    /**
     * @brief add a 'NOT BETWEEN' clause to the query.
     * @method between
     * @public
     * @param string $field
     * @param string|mixed $begin
     * @param string|mixed $end
     */
    public function notBetween($field, $begin, $end, $return = false) {
        $this->statistics['between'] ++;
        $not_between = $this->wrap($field) . ' ' . \Bookworm\Lexicon::key('notbetween')
                . ' ' . $this->createBinding($begin) . ' '
                . \Bookworm\Lexicon::key('and') . ' ' . $this->createBinding($end);
        if ($return) {
            return $not_between;
        }
        $this->clauses['notbetween'][] = $not_between;
        return $this;
    }

    /**
     * @brief add a 'BETWEEN' clause to the query.
     * @method between
     * @public
     * @param string $field
     * @param string|mixed $begin
     * @param string|mixed $end
     */
    public function orBetween($field, $begin, $end, $return = false) {
        $this->statistics['between'] ++;
        $between = $this->wrap($field) . ' ' . \Bookworm\Lexicon::key('between')
                . ' ' . $this->wrap($begin) . ' '
                . \Bookworm\Lexicon::key('and') . ' ' . $this->wrap($end);
        if ($return) {
            return $between;
        }
        $this->clauses['orbetween'][] = $between;
        return $this;
    }

    /**
     * @brief add a 'BETWEEN' clause to the query.
     * @method between
     * @public
     * @param string $field
     * @param string|mixed $begin
     * @param string|mixed $end
     */
    public function orNotBetween($field, $begin, $end, $return = false) {
        $this->statistics['between'] ++;
        $not_between = $this->wrap($field) . ' '
                . \Bookworm\Lexicon::key('notbetween') . ' '
                . $this->createBinding($begin) . ' '
                . \Bookworm\Lexicon::key('and') . ' '
                . $this->createBinding($end);
        if ($return) {
            return $not_between;
        }
        $this->clauses['ornotbetween'][] = $not_between;
        return $this;
    }

    /**
     * @brief add an `LIKE` field to the query
     * @method orLike
     * @public
     * @param string    $field
     * @param string    $argument
     * @param string    $type - optional, defaults to %argument%
     * @param bool      $return optional, if set to true, returns the parsed string instead of the fluent entity.
     * @return          \Bookworm\Builder|string
     */
    public function like($field, $argument, $type = '%a%', $return = false) {
        $this->statistics['like'] ++;
        if (!in_array($type, \Bookworm\Lexicon::$allowed['like'])) {
            $type = \Bookworm\Lexicon::$allowed['like']['default'];
        }

        $final_argument = str_replace('a', $argument, $type);
        $like_clause = $this->wrap($field) . ' ' . \Bookworm\Lexicon::key('like')
                . ' "' . $final_argument . '"';

        if ($return) {
            return $like_clause;
        } else {
            $this->clauses['like'][] = $like_clause;
        }

        return $this;
    }

    /**
     * @brief add an `OR LIKE` field to the query
     * @method orLike
     * @public
     * @param string    $field
     * @param string    $argument
     * @param string    $type - optional, defaults to %argument%
     * @param bool      $return optional, if set to true, returns the parsed string instead of the fluent entity.
     * @return          \Bookworm\Builder|string
     */
    public function orLike($field, $argument, $type = '%a%', $return = false) {
        $this->statistics['like'] ++;
        if (!in_array($type, \Bookworm\Lexicon::$allowed['like'])) {
            $type = \Bookworm\Lexicon::$allowed['like']['default'];
        }
        $final_argument = str_replace('a', $type, $argument);
        $or_like_clause = $this->wrap($field) . ' ' . \Bookworm\Lexicon::key('like')
                . ' "' . $final_argument . '"';

        if ($return) {
            return $or_like_clause;
        } else {
            $this->clauses['orlike'][] = $or_like_clause;
        }

        return $this;
    }

    /**
     * @brief add an `LIKE` field to the query
     * @method orLike
     * @public
     * @param string    $field
     * @param string    $argument
     * @param string    $type - optional, defaults to %argument%
     * @param bool      $return optional, if set to true, returns the parsed string instead of the fluent entity.
     * @return          \Bookworm\Builder|string
     */
    public function notLike($field, $argument, $type = '%a%', $return = false) {
        $this->statistics['like'] ++;
        if (!in_array($type, \Bookworm\Lexicon::$allowed['like'])) {
            $type = \Bookworm\Lexicon::$allowed['like']['default'];
        }
        $final_argument = str_replace('a', $type, $argument);
        $not_like_clause = $this->wrap($field) . ' ' . \Bookworm\Lexicon::key('notlike')
                . ' "' . $final_argument . '"';

        if ($return) {
            return $not_like_clause;
        } else {
            $this->clauses['notlike'][] = $not_like_clause;
        }

        return $this;
    }

    /**
     * @brief add an `OR LIKE` field to the query
     * @method orLike
     * @public
     * @param string $field
     * @param string $argument
     * @param string $type - optional, defaults to %argument%
     * @param bool  $return optional, if set to true, returns the parsed string instead of the fluent entity.
     * @return \Bookworm\Builder|string
     */
    public function orNotLike($field, $argument, $type = '%a%', $return = false) {
        $this->statistics['like'] ++;
        if (!in_array($type, \Bookworm\Lexicon::$allowed['like'])) {
            $type = \Bookworm\Lexicon::$allowed['like']['default'];
        }
        $final_argument = str_replace('a', $type, $argument);
        $or_not_like_clause = $this->wrap($field) . ' ' . \Bookworm\Lexicon::key('notlike')
                . ' "' . $final_argument . '"';

        if ($return) {
            return $or_not_like_clause;
        } else {
            $this->clauses['ornotlike'][] = $or_not_like_clause;
        }

        return $this;
    }

    /**
     * @brief add an `HAVING BY` clause as a having in a secundairy
     * having clause. 
     * @method orHaving
     * @public
     * @param string $field
     * @param string $equality
     * @param string $value
     */
    public function having($field, $equality, $value) {
        $this->statistics['having'] ++;
        if (!in_array($equality, \Bookworm\Lexicon::$allowed['equality'])) {
            $equality = \Bookworm\Lexicon::$allowed['equality']['default'];
        }
        $this->clauses['having'][] = \Bookworm\Lexicon::key('having') . ' '
                . $this->wrap($field) . ' ' . $equality . ' '
                . $this->createBinding($value);
        return $this;
    }

    /**
     * @brief add an `<b>OR</b> HAVING BY` clause as a having in a secundairy
     * having clause. 
     * @method orHaving
     * @public
     * @param string $field
     * @param string $equality
     * @param string $value
     */
    public function orHaving($field, $equality, $value) {
        $this->statistics['having'] ++;
        if (!in_array($equality, \Bookworm\Lexicon::$allowed['equality'])) {
            $equality = \Bookworm\Lexicon::$allowed['equality']['default'];
        }
        $this->clauses['orhaving'][] = \Bookworm\Lexicon::key('having') . ' '
                . $this->wrap($field) . ' ' . $equality . ' '
                . $this->createBinding($value);
        return $this;
    }

    /*
     * Grouping and / or ordering the results
     */

    /**
     * @brief group by a given field and / or add a table to specify which
     * table`s field to group by.
     * @method groupBy
     * @public
     * @param string $field
     * @param string $table - optional
     */
    public function groupBy($field, $table = null) {
        if ($table !== null) {
            $field = $this->wrap($table, $field);
        } else {
            $field = $this->wrap($field);
        }
        $this->clauses['groupby'][] = $field;
        return $this;
    }

    /**
     * @brief add an order by clause to the query.
     * @method orderBy
     * @public
     * @param string $field
     * @param string $direction - defaults to ASC
     * @param string $table - optional
     */
    public function orderBy($field, $direction = null, $table = null) {
        if ($table !== null) {
            $field = $this->wrap($table, $field);
        } else {
            $field = $this->wrap($field);
        }
        if (!in_array($direction, \Bookworm\Lexicon::$allowed['directions'])) {
            $direction = \Bookworm\Lexicon::$allowed['directions']['default'];
        }
        $this->clauses['orderby'][] = $field . ' ' . $direction;
        return $this;
    }

    /**
     * @brief add a limit by clause
     * @param type $limit
     * @param type $page
     * @param type $as_page
     * @return \Bookworm\Builder
     */
    public function limit($limit, $page = null, $as_page = true) {
        if (!is_int($limit) || ($page !== null && !is_int($page))) {
            return $this;
        }
        // store the hard limit so we can retrieve it later on
        $this->limit = $limit;

        if ($page !== null && $as_page) {
            $page = ($page * $limit) - $limit;
        }
        $page_offset = $page !== null ? ', ' . $page : '';
        $this->clauses['limit'] = $limit . $page_offset;

        return $this;
    }

    /*
     * Finally, we need some closing functionality to retrieve the query 
     * and / or the bindings etc, basically, all the get methods we can access
     * that "break" fluency.
     */

    /**
     * @brief returns the final query as a string, parameterized. If you need
     * the bindings, you can call $QueryBuilder->getBindings() to retrieve them.
     * @method get
     * @public
     * @param int $limit - optional
     * @return string
     */
    public function get($limit = null) {
        if ($limit !== null) {
            $this->limit($limit);
        }
        return $this->buildQuery();
    }

    /**
     * @brief returns all the bindings we found and used in the query
     * @method getBindings
     * @public
     * @return array
     */
    public function getBindings() {
        return $this->bindings;
    }

    /**
     * @brief returns all the binding fields' types.
     * @method getBindingTypes
     * @public
     * @return array
     */
    public function getBindingTypes() {
        if (count($this->bindingTypes) > 0) {
            return $this->bindingTypes;
        }
        return null;
    }

    /**
     * @brief if the last query this builder created had a limit clause, we return
     * the limit as defined by the limit() method. 
     * @method getLimit
     * @public
     * @return int
     */
    public function getLimit() {
        if ($this->clauses['limit'] !== null) {
            return $this->limit;
        }
        return null;
    }

    /*
     * Building the query and it`s associating clauses
     */

    /**
     * @brief a helper to check what kind of query we`re performing. As it matters
     * that we dont mixed up delete with update queries or do an insert on a select
     * query. 
     * @method buildQuery
     * @protected
     * @return \Bookworm\Builder
     */
    protected function buildQuery() {
        // reset the string to nothing
        $this->query = '';

        if (count($this->clauses['select']) > 0) {
            return $this->buildSelectQuery();
        }
        if (count($this->clauses['update']) > 0) {
            return $this->buildUpdateQuery();
        }
        if (count($this->clauses['insert']) > 0) {
            return $this->buildInsertQuery();
        }
        if (count($this->clauses['delete']) > 0) {
            return $this->buildDeleteQuery();
        }
    }

    /**
     * @brief Create the query string for a `SELECT` Query.
     * @method buildSelectQuery
     * @protected
     * @returns \Bookworm\Builder
     */
    protected function buildSelectQuery() {
        $this->query = \Bookworm\Lexicon::key('select') . ' '
                . implode(', ', $this->clauses['select']) . ' '
                . \Bookworm\Lexicon::key('from') . ' '
                . implode(', ', $this->clauses['from']) . ' ';

        if ($this->statistics['joins'] > 0) {
            $this->query .= $this->buildJoinQuery();
        }
        if ($this->statistics['where'] > 0) {
            $this->query .= $this->buildWhereQuery();
        }
        if ($this->statistics['between'] > 0) {
            $this->query .= $this->buildBetweenQuery();
        }
        if ($this->statistics['like'] > 0) {
            $this->query .= $this->buildLikeQuery();
        }

        if (count($this->clauses['groupby']) > 0) {
            $this->query .= \Bookworm\Lexicon::key('groupby') . ' '
                    . implode(', ', $this->clauses['groupby']) . ' ';
        }

        if (count($this->clauses['orderby']) > 0) {
            $this->query .= \Bookworm\Lexicon::key('orderby') . ' '
                    . implode(', ', $this->clauses['orderby']) . ' ';
        }

        if ($this->clauses['limit'] !== null && $this->clauses['limit'] !== '') {
            $this->query .= \Bookworm\Lexicon::key('limit') . ' '
                    . $this->clauses['limit'] . '';
        }

        $this->query = trim($this->query) . ';';
        return $this->query;
    }

    /**
     * @brief Create the query string for a `SELECT` Query.
     * @method buildSelectQuery
     * @protected
     * @returns \Bookworm\Builder
     */
    protected function buildUpdateQuery() {
        $this->query = \Bookworm\Lexicon::key('update') . ' '
                . $this->wrap($this->clauses['update']) . ' '
                . \Bookworm\Lexicon::key('set') . ' ';

        if (count($this->clauses['set']) == 0) {
            return false;
        }

        $this->query .= implode(', ', $this->clauses['set']) . ' ';

        if ($this->statistics['where'] > 0) {
            $this->query .= $this->buildWhereQuery();
        }
        if ($this->statistics['between'] > 0) {
            $this->query .= $this->buildBetweenQuery();
        }
        if ($this->statistics['like'] > 0) {
            $this->query .= $this->buildLikeQuery();
        }

        if (count($this->clauses['limit']) == 1) {
            $this->query .= \Bookworm\Lexicon::key('limit') . ' '
                    . $this->clauses['limit'] . '';
        }

        $this->query = trim($this->query) . ';';
        return $this->query;
    }

    /**
     * @brief Create the query string for a `SELECT` Query.
     * @method buildSelectQuery
     * @protected
     * @returns \Bookworm\Builder
     */
    protected function buildInsertQuery() {
        $this->query = \Bookworm\Lexicon::key('insert') . ' '
                . $this->wrap($this->clauses['insert']) . ' ';
        if (count($this->clauses['fieldnames'])) {
            $this->query .= ' (' . implode(', ', $this->clauses['fieldnames']) . ') ';
        }
        $this->query .= \Bookworm\Lexicon::key('values') . ' '
                . implode(', ', $this->clauses['values']) . ' ';
        $this->query = trim($this->query) . ';';
        return $this->query;
    }

    /**
     * @brief build the where clauses as a query string which we can then append
     * to the final query string.
     * @method buildWhereQuery
     * @protected
     * @return string
     */
    protected function buildWhereQuery() {
        $intermediary = \Bookworm\Lexicon::key('where') . ' ';
        if (count($this->clauses['where']) > 0) {
            $intermediary .= implode(' ' . \Bookworm\Lexicon::key('and')
                            . ' ', $this->clauses['where']) . ' ';
        }
        if (count($this->clauses['orwhere']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('or') . ' '
                    . implode(' ' . \Bookworm\Lexicon::key('or')
                            . ' ', $this->clauses['orwhere']) . ' ';
        }

        return $intermediary;
    }

    /**
     * @brief build the query string and add the `BETWEEN` clauses to the final query.
     * This method returns a formatted string where all the different `between` 
     * clauses are added.
     * @method buildBetweenQuery
     * @protected
     * @return string
     */
    protected function buildBetweenQuery() {
        $intermediary = '';
        // All the between clauses
        if (count($this->clauses['between']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('and') . ' ' . implode(' '
                            . \Bookworm\Lexicon::key('and') . ' ', $this->clauses['between']) . ' ';
        }

        if (count($this->clauses['notbetween']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('and') . ' ' . implode(' '
                            . \Bookworm\Lexicon::key('and') . ' ', $this->clauses['notbetween']) . ' ';
        }

        if (count($this->clauses['orbetween']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('or') . ' ' . implode(' '
                            . \Bookworm\Lexicon::key('or') . ' ', $this->clauses['orbetween']) . ' ';
        }

        if (count($this->clauses['ornotbetween']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('or') . ' ' . implode(' '
                            . \Bookworm\Lexicon::key('or') . ' ', $this->clauses['ornotbetween']) . ' ';
        }
        return $intermediary;
    }

    /**
     * @brief build the query string and add the `LIKE` clauses to the final query.
     * This method returns a formatted string where all the different `LIKE` 
     * clauses are added.
     * @method buildLikeQuery
     * @protected
     * @return string
     */
    protected function buildLikeQuery() {
        $intermediary = '';
        //  All the LIKE clauses
        if (count($this->clauses['like']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('and') . ' ' . implode(' '
                            . \Bookworm\Lexicon::key('and') . ' ', $this->clauses['like']) . ' ';
        }
        if (count($this->clauses['orlike']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('or') . ' ' . implode(' '
                            . \Bookworm\Lexicon::key('or') . ' ', $this->clauses['orlike']) . ' ';
        }
        if (count($this->clauses['notlike']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('and') . ' ' . implode(' '
                            . \Bookworm\Lexicon::key('and') . ' ', $this->clauses['notlike']) . ' ';
        }
        if (count($this->clauses['ornotlike']) > 0) {
            $intermediary .= \Bookworm\Lexicon::key('or') . ' ' . implode(' '
                            . \Bookworm\Lexicon::key('or') . ' ', $this->clauses['ornotlike']) . ' ';
        }
        return $intermediary;
    }

    /**
     * @brief build the query string and add the `JOIN` clauses to the final query.
     * This method returns a formatted string where all the different `JOIN` 
     * clauses are added.
     * @method buildJoinQuery
     * @protected
     * @return string
     */
    protected function buildJoinQuery() {
        $intermediary = '';
        //  All the LIKE clauses
        if (count($this->clauses['innerjoin']) > 0) {
            $intermediary .= implode(' ', $this->clauses['innerjoin']) . ' ';
        }

        return $intermediary;
    }

    /*
     * Helper functions to sanitize the fields 
     */

    /**
     * @brief reset the query builder and restore all the previously filled fields
     * back to null. 
     * @method reset
     * @public
     * @return \Bookworm\Builder
     */
    public function reset() {
        $this->initialized = false;
        $this->query = '';
        $this->clauses = self::$clauses_template;
        $this->bindings = [];
        foreach ($this->statistics as $k => $v) {
            $this->statistics[$k] = 0;
        }
        return $this;
    }

    /**
     * @brief create a binding for the value given and return the bound hash.
     * @method createBinding
     * @protected
     * @param mixed $value
     * @param mixed $type - optional
     * @return string
     */
    protected function createBinding($value, $type = null) {
        if (is_array($value)) {
            return '(' . implode(',', $value) . ')';
        }
        $hash = \Bookworm\Utilities::hash();
        $this->bindings[$hash] = $value;
        if ($type !== null) {
            $this->bindingTypes[$hash] = $type;
        }
        return $hash;
    }

    /**
     * @brief wrap the field or table ( or both ) inside escape quotes. 
     * @method wrap
     * @protected
     * @param string $value
     * @param [, string optional]
     * @return string
     */
    protected function wrap($value) {
        if (func_num_args() > 1) {
            return '`' . implode('`.`', func_get_args()) . '`';
        } else {
            return '`' . $value . '`';
        }
    }

    /**
     * @brief given any argument, this function will return an integer no matter what.
     * If the string given was not integer, returns a int 0. 
     * @method toNumber
     * @protected
     * @param   mixed $value
     * @return  int
     */
    protected function toNumber($value) {

        if (!is_float($value) || is_double($value) || is_int($value) || !ctype_digit($value)) {
            $value = 0;
        }

        return intval($value);
    }

    /**
     * @brief a slitted string wrap in escape quotes. If there`s a table preceding
     * the field, we explode on the dot and return each argument wrapped in the
     * escape quotes.
     * @method splitwrap
     * @protected
     * @param string $value
     * @return string
     */
    protected function splitwrap($value, $target = '.') {
        if (strpos($value, $target)) {
            $arguments = explode($target, $value);
            $args = array_map(function($row) {
                return $this->wrap($row);
            }, $arguments);
            return implode($target, $args);
        } else {
            return $this->wrap($value);
        }
    }

    /**
     * @brief analyse the array ( and check for a valid array ) and return
     * whatever was used as an operator to check. This is useful for checking
     * whether you wanted an inner LIKE or BETWEEN clause for example.
     * @method extractWhereClause
     * @protected
     * @param array $type
     * @param bool $and defaults to true, if false, will check for OR clauses
     * @return string
     */
    protected function extractWhereClause($type, $and = true) {
        if (!is_array($type)) {
            return '';
        }

        if ($and) {
            switch ($type[1]) {
                case 'between': return $this->between($type[0], $type[2], $type[3], true);
                case 'notbetween': return $this->notbetween($type[0], $type[2], $type[3], true);
                case 'like': return $this->like($type[0], $type[2], isset($type[3]) ? $type[3] : null, true);
                case 'notlike': return $this->like($type[0], $type[2], isset($type[3]) ? $type[3] : null, true);
                default: return $this->where($type[0], $type[1], $type[2], true);
            }
        } else {
            switch ($type[1]) {
                case 'orbetween': return $this->orbetween($type[0], $type[2], $type[3], true);
                case 'ornobetween': return $this->ornotbetween($type[0], $type[2], $type[3], true);
                case 'orlike': return $this->orlike($type[0], $type[2], isset($type[3]) ? $type[3] : null, true);
                case 'ornotlike': return $this->ornotlike($type[0], $type[2], isset($type[3]) ? $type[3] : null, true);
                default: return $this->orwhere($type[0], $type[1], $type[2], true);
            }
        }
        return '';
    }

    /**
     * @brief a helper method to check if a where clause is an array and contains
     * 3 or 4 values. 
     * @method isWhereArray
     * @protected
     * @param mixed $mixed
     * @return bool
     */
    protected function isWhereArray($mixed) {
        return ( is_array($mixed) and ( count($mixed) == 3 || count($mixed) == 4));
    }

}
