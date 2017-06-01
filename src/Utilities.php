<?php

namespace Bookworm;

/**
 * @class Utilities
 * @public
 * @author Merijn <merijn@exinium.nl> 
 */
class Utilities {
    
    /**
     * @brief A random hash string generator, used for creating dynamic binds.
     * @method hash
     * @static
     * @public
     * @param {int} $length
     * @return {string}
     */
    public static function hash($length = 6, $prefix = ':') {
        $ln = \Bookworm\Utilities::clamp($length, 4, 12);
        $ch = 'abcdefghijklmnopqrstuvwyz';
        $chln = strlen($ch);
        $hash = '';
        for ($i = 0; $i < $ln; $i++) {
            $hash .= $ch[rand(0, $chln - 1)];
        }
        if (!is_string($prefix)) {
            $prefix = '';
        }
        return $prefix . $hash;
    }

    /**
     * @brief A random hash string generator, used for creating dynamic binds.
     * @method hash
     * @static
     * @public
     * @param {int} $length
     * @return {string}
     */
    public static function inthash($length = 6){
        $ln = \Bookworm\Utilities::clamp($length, 4, 12);
        $ch = '0123456789';
        $chln = strlen($ch);
        $ihash = '';
        for($i = 0; $i < $ln; $i++){
            $ihash .= $ch[rand(0, $chln - 1)];
        }
        return $ihash;
    }
    
    /**
     * @brief clamp a number between 2 values, if the bounds are reached, return
     * the bound.
     * @method clamp
     * @static
     * @public
     * @param {int} $val
     * @param {int} $min
     * @param {int} $max
     * @return {int}
     */
    public static function clamp($val, $min, $max) {
        return $val < $min ? $min : ( $val > $max ? $max : $val);
    }
    
    /**
     * @brief a helper to check if a given field has a given flag.
     * @method hasFlag
     * @public
     * @param   array   $field
     * @param   string  $flag
     * @return  bool
     */
    public static function hasFlag(array $field, $flag) {
        foreach ($field as $flags) {
            if ($flags === $flag) {
                return true;
            }
        }
        return false;
    }
    
    
    /**
     * @brief returns a default value for any type given. 
     * @method getDefaultValue
     * @public
     * @param string $type
     * @return mixed
     */
    public static function getDefaultValue($type) {
        switch ($type) {
            case 'TIMESTAMP': case 'DATE': case 'DATETIME': return 0;
            case 'LONG': case 'INT': case 'TINY': case 'MEDIUM': return 0;
            case 'DOUBLE': case 'FLOAT': return 0.0; 
            case 'VAR_STRING': default: return '';
        }
    }
    
    /**
     * @brief returns a default value for any type given. 
     * @method getDefaultValue
     * @public
     * @param string $type
     * @return mixed
     */
    public static function getParamType($type) {
        switch ($type) {
            case 'TIMESTAMP': case 'DATE': case 'DATETIME': return \PDO::PARAM_STR;
            case 'LONG': case 'INT': case 'TINY': case 'MEDIUM': return \PDO::PARAM_INT;
            case 'DOUBLE': case 'FLOAT': return \PDO::PARAM_STR; 
            case 'BOOL': case 'BOOLEAN': return \PDO::PARAM_BOOL;
            case 'VAR_STRING': default: return \PDO::PARAM_STR;
        }
    }
    
}