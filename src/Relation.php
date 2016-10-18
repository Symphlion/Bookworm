<?php

namespace Bookworm;

use \Bookworm\Pool;


class Relation extends Query {

    /**
     * @var int
     */
    protected $_relation_query_id = [];

    /**
     * @brief define a one to one relation with a given model, optionally, 
     * you can pass along the foreign key and table if you want to retrieve a 
     * non-uniform table. 
     * @method hasOne
     * @public
     * @param string $model
     * @param string $foreign_key   optional
     * @return \Bookworm\Model
     */
    public function hasOne($model, $foreign_key = null) {
        
        $relation = $this->createRelation($model);
        
        if( $relation === null ){
            return null;
        }
        
        if ($foreign_key === null) {
            $foreign_key = strtolower(Pool::getTable( $this->_table_id )->getSingular()) . '_id';
        }

        return $relation->setRelation($this)->where($foreign_key, '=', $this->getId())->first();
    }

    /**
     * @brief the inverse of the hasOne relation, which you can use to get the parent
     * object back. 
     * @method belongsTo
     * @param string    $model
     * @param string    $related_own_key
     * @return \Bookworm\Model
     */
    public function belongsTo($model, $related_own_key = null) {
        
        $relation = $this->createRelation($model, "belongs-to");
        
        if( $relation === null ){
            return null;
        }
        
        if ($related_own_key === null) {
            $related_own_key = Pool::getTable($relation->_table_id)->getSingular() . '_id';
        }
        return $relation->setRelation($this)->where('id', '=', $this->$related_own_key)->first();
    }

    /**
     * @brief returns the many attached models found by the primary ID from the 
     * current model and where it matches the pivot table.
     * @method hasMany
     * @public
     * @param string    $model
     * @param string    $pivot_table  optional, defaults to null
     * @param string    $foreign_id optional, defaults to null
     * @param string    $foreign_table optional, defaults to null
     */
    public function hasMany($model, $foreign_key = null, $own_key = null) {
        if (!is_string($model)) {
            return null;
        }
        $relation = $this->createRelation($model, 'has-many');
        if( $relation === null ){
            return null;
        }
        if ($foreign_key === null) {
            $foreign_key = strtolower( Pool::getTable( $this->_table_id)->getSingular()) . '_id';
        }
        
        
        return call_user_func_array(array($model, 'where'), [$foreign_key, '=', $this->getId()])->all();
        
    }
    
    /**
     * @brief create a related RelationObject where we can get the table specific
     * details from. 
     * @method createRelation
     * @protected
     * @param   string $model
     * @param   string $relation_type
     * @return  \Bookworm\model
     */
    protected function createRelation ( $model, $relation_type )
    {
        if ($model !== '' && is_string($model))
        {
            try 
            {
                $relation = new $model(false, false);
                return $relation;    
            } 
            catch (\Exception $e) 
            {
                $this->_errors[] = $e->getMessage();
                if (! ( $relation instanceof \Bookworm\Model ) )
                {
                    $this->_errors["relation-model-" . $relation_type ] = "The relation specified could not be instantiated. The model`s name given is not of type \Bookworm\Model. The model given: <b>" . $model . "</b>";
                    return null;
                }
            }
        }
        return null;
    }

    /**
     * @brief set up the relation`s calling-model to specify who`s calling in this
     * object.
     * @method setRelation
     * @public
     * @param \Bookworm\Model $model
     */
    public function setRelation(\Bookworm\Model $model) {
        $this->_relation_query_id[] = $model->getUniqueId();
        return $this;
    }

}
