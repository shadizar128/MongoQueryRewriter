<?php
namespace Lib\Mongo\QueryRewrite;
use Lib\Mongo\QueryRewrite\Exceptions\QueryRewriteException;

/**
 * Class QueryRewrite
 * @package Lib\Mongo\QueryRewrite
 */
class QueryRewrite {

    const ON_FAILURE_RETURN_ORIGINAL_QUERY          = 0x01;
    const ON_FAILURE_RETURN_IMPOSSIBLE_QUERY        = 0x02;
    const ON_FAILURE_THROW_EXCEPTION                = 0x03;

    const DEFAULT_MAX_OR_CONDITIONS                 = 25;

    /**
     * @var CollisionHandler
     */
    protected $_collisionHandler;

    /**
     * Constructor
     * @param CollisionHandler $collisionHandler
     */
    public function __construct(CollisionHandler $collisionHandler) {
        $this->_collisionHandler = $collisionHandler;
    }

    /**
     * Optimizes a MongoDB query
     *
     * @param array $conditions Set of conditions to optimize
     * @param array $params Additional parameters
     * @return array Optimized filter
     * @throws \Exception
     */
    public function rewrite($conditions, $params = array()) {

        $params = array_merge(
            array(
                'maxOrConditions' => self::DEFAULT_MAX_OR_CONDITIONS,
                'onFailure' => self::ON_FAILURE_RETURN_ORIGINAL_QUERY
            ),
            $params
        );

        try {

            // try to optimize query
            $result = $this->_rewrite($conditions, $params);

            // max $or conditions reached
            if ($params['maxOrConditions'] && isset($result['$or']) && count($result['$or']) > $params['maxOrConditions']) {
                return $conditions;
            } else {
                return $result;
            }

        } catch (\Exception $ex) {

            if ($params['onFailure'] == self::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY) {
                return array('_id' => null);
            }

            if ($params['onFailure'] == self::ON_FAILURE_RETURN_ORIGINAL_QUERY) {
                trigger_error('Could not optimize query: ' . $ex->getMessage());
                return $conditions;
            }

            // re-throw
            throw $ex;

        }

    }

    /**
     * Optimizes a MongoDB query
     *
     * @param array $conditions Set of conditions to optimize
     * @param array $params Additional parameters
     * @return array Optimized filter
     * @throws QueryRewriteException
     */
    protected function _rewrite($conditions, $params = array()) {

        // optimize or conditions
        $orConditions = $this->_optimizeOr($conditions, $params);

        // optimize and conditions
        $andConditions = $this->_optimizeAnd($conditions, $params);

        // optimize simple field conditions
        $simpleConditions = $this->_optimizeOther($conditions, $params);

        if (!empty($orConditions)) {

            // merge all single field conditions in each set of $or conditions
            if (!empty($simpleConditions)) {
                $orConditions = $this->_mergeOrConditions($orConditions, array($simpleConditions));
            }

        } else {

            // replace or conditions with simple conditions
            $orConditions = array($simpleConditions);

        }

        // merge $and conditions with $or conditions
        if (isset($andConditions['$or'])) {
            // if $or operator exists there are no other conditions in the array
            $orConditions = $this->_mergeOrConditions($orConditions, $andConditions['$or']);
        } else {
            // if $or operator does not exists then all conditions are in an implicit $and
            $orConditions = $this->_mergeOrConditions($orConditions, array($andConditions));
        }

        // get number of $or conditions
        $count = count($orConditions);

        if ($count == 0) {
            return array();
        } else if ($count == 1) {
            // remove $or operator for single set of conditions
            return array_pop($orConditions);
        } else {
            // add $or operator for multiple sets of conditions
            return array('$or' => $orConditions);
        }

    }

    /**
     * Optimize $or operator if it exists, raise exception if all conditions are invalid
     *
     * @param array $conditions
     * @param array $params
     * @return array|mixed
     * @throws QueryRewriteException
     */
    protected function _optimizeOr($conditions, $params) {

        if (empty($conditions['$or'])) {

            // nothing to do
            return array();

        } else {

            $result = $this->_optimizeOrConditions($conditions['$or'], $params);
            if (empty($result)) {
                throw new QueryRewriteException();
            }

            return $result;

        }

    }

    /**
     * Optimize $and operator if it exists, raise exception if all conditions are invalid
     *
     * @param array $conditions
     * @param array $params
     * @return array|mixed
     * @throws QueryRewriteException
     */
    protected function _optimizeAnd($conditions, $params) {

        if (empty($conditions['$and'])) {
            return array();
        } else {
            return $this->_optimizeAndConditions($conditions['$and'], $params);
        }

    }

    /**
     * Optimize simple conditions
     * @param $conditions
     * @param $params
     * @return mixed
     * @throws QueryRewriteException
     */
    protected function _optimizeOther($conditions, $params) {

        // unset $or/$and operators
        unset($conditions['$or']);
        unset($conditions['$and']);

        foreach ($conditions as $field => $condition) {
            $conditions[$field] = $this->_optimizeFieldCondition($this->_collisionHandler->handleFieldCollision($condition, array()));
        }

        return $conditions;

    }

    /**
     * Optimize conditions from an $and operator, the result can be:
     * - a set of conditions with implicit $and between them
     * - a single condition with the $or operator which contains all other conditions
     *
     * @param array $conditions
     * @param array $params
     * @return array|mixed
     * @throws QueryRewriteException
     */
    protected function _optimizeAndConditions($conditions, $params = array()) {

        // set of $or conditions
        $orConditions = array();

        // single field conditions
        $singleFieldConditions = array();

        foreach ($conditions as $andConditionSet) {

            // optimize condition
            $andConditionSet = $this->_rewrite($andConditionSet);

            if (isset($andConditionSet['$or'])) {
                // if $or operator exists there are no other conditions in the array, merge or conditions
                $orConditions = $this->_mergeOrConditions($orConditions, $andConditionSet['$or']);
            } else {
                // if $or operator does not exists merge conditions with the other single field conditions
                $singleFieldConditions = $this->_mergeAndConditions($singleFieldConditions, $andConditionSet);
            }

        }

        if (empty($orConditions)) {
            return $singleFieldConditions;
        }

        // merge single field conditions with each set of $or conditions
        foreach ($orConditions as $index => $orConditionSet) {
            $mergeResult = $this->_mergeAndConditions($orConditionSet, $singleFieldConditions);
            if ($mergeResult) {
                $orConditions[$index] = $mergeResult;
            }
        }

        // get number of $or conditions
        $count = count($orConditions);

        if ($count == 0) {
            return array();
        } else if ($count == 0) {
            // remove $or operator for single set of conditions
            return array_pop($orConditions);
        } else {
            // add $or operator for multiple sets of conditions
            return array('$or' => $orConditions);
        }

    }

    /**
     * Optimize conditions from an $or operator, the result can be:
     *
     * @param array $conditions
     * @param array $params
     * @return array|mixed
     * @throws QueryRewriteException
     */
    protected function _optimizeOrConditions($conditions, $params = array()) {

        // separate $and operator from the rest of the conditions
        $newConditions = array();

        foreach ($conditions as $orConditionSet) {

            // optimize condition
            $orConditionSet = $this->_rewrite($orConditionSet);

            if (empty($orConditionSet)) {

                // all conditions were invalid

            } else if (isset($orConditionSet['$or'])) {

                // if $or operator exists there are no other conditions in the array
                $secondTierOr = $orConditionSet['$or'];

                // bring all sets from the second tier up to the main tier
                foreach ($secondTierOr as $secondTierSet) {
                    $newConditions[] = $secondTierSet;
                }

            } else {

                // if $or operator does not exist add the entire set to the main tier
                $newConditions[] = $orConditionSet;

            }

        }

        return $newConditions;

    }

    /**
     * Optimize a single field condition
     *
     * @param mixed $condition
     * @return mixed
     */
    public function optimizeFieldCondition($condition) {
        return $this->_optimizeFieldCondition($condition);
    }

    /**
     * Optimize a single field condition
     *
     * @param mixed $condition
     * @return mixed
     */
    protected function _optimizeFieldCondition($condition) {

        if (is_array($condition)) {

            if (isset($condition['$in']) && count($condition) == 1 && count($condition['$in']) == 1) {
                // remove $in if single value and no other conditions like $gt, $nin, etc present
                $condition = array_pop($condition['$in']);
            } else if (isset($condition['$nin']) && count($condition['$nin']) == 1) {
                // transform $nin to $ne if single value
                $condition['$ne'] =  array_pop($condition['$nin']);
                unset($condition['$nin']);
            }

        }

        return $condition;

    }

    /**
     * Merge two sets of $or conditions
     *
     * @param array $setA
     * @param array $setB
     * @return array
     * @throws QueryRewriteException
     */
    protected function _mergeOrConditions($setA, $setB) {

        $result = array();

        // return the non empty set
        if (empty($setA)) {
            return $setB;
        }

        if (empty($setB)) {
            return $setA;
        }

        foreach ($setA as $rulesFromA) {
            foreach ($setB as $rulesFromB) {
                try {

                    $result[] = $this->_mergeAndConditions($rulesFromA, $rulesFromB);

                } catch (QueryRewriteException $ex) {
                    // exception caught
                }
            }
        }

        $count = count($result);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($result[$i] == $result[$j]){
                    //Logger::error('$i', $result[$i]);
                    //Logger::error('$j', $result[$j]);
                    unset($result[$i]);
                    continue 2;
                }
            }
        }

        // all combinations were invalid?
        if (empty($result)) {
            throw new QueryRewriteException();
        }

        return array_values($result);

    }

    /**
     * Merge two sets of $and conditions
     *
     * @param array $setA
     * @param array $setB
     * @return array
     * @throws QueryRewriteException
     */
    protected function _mergeAndConditions($setA, $setB) {

        // get common keys
        $commonKeys = array_keys(array_intersect_key($setA, $setB));

        $result = array_merge(array_diff_key($setA, $setB), array_diff_key($setB, $setA));
        foreach ($commonKeys as $commonKey) {
            $result[$commonKey] = $this->_optimizeFieldCondition($this->_collisionHandler->handleFieldCollision($setA[$commonKey], $setB[$commonKey]));
        }

        return $result;

    }

} 