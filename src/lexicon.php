<?php

namespace Bookworm;

/**
 * @brief a Lexicon class containing all the reserved keywords for the operations
 * we`re offering. If you need a custom value, just change the lexicon and you should
 * be good to go. If something else requires changing, overwrite the Builder class
 * and extend it to your needs.
 * @class Lexicon
 */
class Lexicon {
    
    /**
     * The actual lexicon containing all the useful keywords. 
     * @var array
     */
    protected static $lexicon = [
        'or' => 'or',
        'and' => 'and',
        'set' => 'set',
        'where' => 'where',
        'like' => 'like',
        'notlike' => 'not like',
        'from' => 'from',
        'select' => 'select', 
        'update' => 'update',
        'delete' => 'delete',
        'values' => 'values',
        'insert' => 'insert into',
        'having' => 'having',
        'between' => 'between',
        'nothaving' => 'not having',
        'notbetween' => 'not between',
        'groupby' => 'group by',
        'orderby' => 'order by',
        'notin' => 'not in',
        'limit' => 'limit',
        'not' => 'not',
        'in' => 'in',
        'on' => 'on',
        'notin' => 'not in',
        'innot' =>  'in not',
        'innerjoin' => 'inner join',
        'leftjoin' => 'left join',
        'rightjoin' => 'right join',
    ];
    
    /**
     * @property all the allowed properties, either direction, or logical or
     * equality operators, etc. 
     * @var array
     */
    public static $allowed = [
        'directions' => [
            'asc', 'desc',
            'default' => 'asc'
        ],
        'logical' => [
            'and' => 'or',
            'default' => 'and'
        ],
        'equality' => [
            '>', '>=', '=', '<=', '<', '!=', 'IN', 'NOT IN',
            'default' => '='
        ],
        'like' => [
            '%a%', 'a%', '%a',
            'default' => '%a%'
        ]
    ];
    
    /**
     * @brief a way to access the key and have it returned to us in a static context.
     * @method key
     * @public
     * @static
     * @param string $key
     * @return string|false
     */
    public static function key( $key ){
        if( !isset(self::$lexicon[$key])){
            return false;
        }
        return strtoupper( self::$lexicon[ $key ]);
    }
    
    /**
     * @brief a way to access the key and have it returned to us in a static context.
     * @method key
     * @public
     * @static
     * @param string $operand
     * @param string $type
     * @return string|false
     */
    public static function validate( $key, $group ){
        if( !in_array($group, ['directions', 'logical', 'equality', 'like'])){
            return null;
        }
        if( !in_array( $key, self::$allowed[ $group ])){
            return strtoupper(self::$allowed[$group]['default']);
        }
        return strtoupper($key);
    }
    
    /**
     * The fields to select or where the action is on.
     * @property $_searches
     * @protected
     * @type {array}
     */
    protected $_searches = ['*'];

    /**
     * @property $_attr_values
     * @protected
     * @type array
     */
    protected $_attr_values = [];

    /**
     * @property $_binds
     * @protected
     * @type array
     */
    protected $_binds = [];
}