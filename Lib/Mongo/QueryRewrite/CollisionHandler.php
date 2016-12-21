<?php
namespace Lib\Mongo\QueryRewrite;
use Lib\Mongo\QueryRewrite\Exceptions\QueryRewriteException;

class CollisionHandler {

    /**
     * Handle field collision and merges values
     *
     * @param mixed $first
     * @param mixed $second
     * @return array
     * @throws QueryRewriteException Raise error on contradiction
     */
    public function handleFieldCollision($first, $second) {

        // type check
        $firstType  = gettype($first);
        $secondType = gettype($second);

        // both are not arrays
        if ($firstType != 'array' && $secondType != 'array') {

            // compare class and attributes
            $different = ($firstType == 'object' && $secondType == 'object') ? ($first != $second) : $first !== $second;
            if ($different) {
                // cannot merge invalid conditions
                throw new QueryRewriteException('Field cannot have two values at once');
            } else {
                return $first;
            }

        }

        // convert to array
        if ($firstType != 'array') {
            $first = array('$in' => array($first));
        }

        // convert to array
        if ($secondType != 'array') {
            $second = array('$in' => array($second));
        }

        // get list of processing methods
        $processors = $this->_getProcessors();

        // prepare sets
        $this->_prepareSet($first);
        $this->_prepareSet($second);

        // compare each set with itself (remove $operator key from the second set)
        foreach ($processors as $operator => $method) {
            $diff = array_diff_key($first, array($operator => true));
            call_user_func_array(array($this, $method), array(&$first, &$diff));
        }
        foreach ($processors as $operator => $method) {
            $diff = array_diff_key($second, array($operator => true));
            call_user_func_array(array($this, $method), array(&$second, &$diff));
        }

        // compare each set with the other in a symmetric way
        foreach ($processors as $operator => $method) {
            call_user_func_array(array($this, $method), array(&$first, &$second));
        }
        foreach ($processors as $operator => $method) {
            call_user_func_array(array($this, $method), array(&$second, &$first));
        }

        // merge result
        return array_merge($first, $second);

    }

    /**
     * Return true if the first value is lesser than the second
     * Implemented in case we need to compare mongo types
     *
     * @param mixed $a First value
     * @param mixed $b Second value
     * @param bool|true $strict Strict check
     * @return bool
     */
    protected function _isLesser($a, $b, $strict = true) {
        return $strict? ($a < $b) : ($a <= $b);
    }

    /**
     * Return true if the first value is greater than the second
     * Implemented in case we need to compare mongo types
     *
     * @param mixed $a First value
     * @param mixed $b Second value
     * @param bool|true $strict Strict check
     * @return bool
     */
    protected function _isGreater($a, $b, $strict = true) {
        return $strict? ($a > $b) : ($a >= $b);
    }

    /**
     * Return true if the two values are equal
     * Implemented in case we need to compare mongo types
     *
     * @param mixed $a First value
     * @param mixed $b Second value
     * @param bool|true $strict Strict check
     * @return bool
     */
    protected function _isEqual($a, $b, $strict = true) {
        return $strict? ($a == $b) : ($a === $b);
    }

    /**
     * Get list of processor methods
     *
     * @return array
     */
    protected function _getProcessors() {
        return array(
            '$gt'   => '_processGreater',
            '$gte'  => '_processGreaterOrEqual',
            '$lt'   => '_processLesser',
            '$lte'  => '_processLesserOrEqual',
            '$in'   => '_processIn',
            '$nin'  => '_processNotIn'
        );
    }

    /**
     * Prepare set for comparison
     *
     * @param $set
     */
    protected function _prepareSet(&$set) {

        // inject $ne into $nin
        if (isset($set['$ne'])) {

            if (!isset($set['$nin'])) {
                $set['$nin'] = array();
            }

            $set['$nin'] = array_merge($set['$nin'], array($set['$ne']));
            unset($set['$ne']);

        }

    }

    /**
     * Process $gt operator from the first set
     *
     * @param array $first
     * @param array $second
     * @throws QueryRewriteException
     */
    protected function _processGreater(&$first, &$second) {

        if (!isset($first['$gt'])) {
            return;
        }

        if (isset($second['$lt']) && $this->_isLesser($second['$lt'], $first['$gt'], false)) {
            throw new QueryRewriteException('Invalid condition');
        }

        if (isset($second['$lte']) && $this->_isLesser($second['$lte'], $first['$gt'], false)) {
            throw new QueryRewriteException('Invalid condition');
        }

        if (isset($second['$gt'])) {

            if ($this->_isGreater($first['$gt'], $second['$gt'])) {
                unset($second['$gt']);
            } else {
                unset($first['$gt']);
                return;
            }

        }

        if (isset($second['$gte'])) {

            if ($this->_isGreater($first['$gt'], $second['$gte'], false)) {
                unset($second['$gte']);
            } else {
                unset($first['$gt']);
                return;
            }

        }

    }

    /**
     * Process $gte operator from the first set
     *
     * @param array $first
     * @param array $second
     * @throws QueryRewriteException
     */
    protected function _processGreaterOrEqual(&$first, &$second) {

        if (!isset($first['$gte'])) {
            return;
        }

        if (isset($second['$lt']) && $this->_isLesser($second['$lt'], $first['$gte'], false)) {
            throw new QueryRewriteException('Invalid condition');
        }

        if (isset($second['$lte']) && $this->_isLesser($second['$lte'], $first['$gte'])) {
            throw new QueryRewriteException('Invalid condition');
        }

        if (isset($second['$gt'])) {

            if ($this->_isGreater($first['$gte'], $second['$gt'])) {
                unset($second['$gt']);
            } else {
                unset($first['$gte']);
                return;
            }

        }

        if (isset($second['$gte'])) {

            if ($this->_isGreater($first['$gte'], $second['$gte'])) {
                unset($second['$gte']);
            } else {
                unset($first['$gte']);
                return;
            }

        }

        if (isset($second['$nin']) && in_array($first['$gte'], $second['$nin'])) {
            $first['$gt'] = $first['$gte'];
            unset($first['$gte']);
        }

    }

    /**
     * Process $lt operator from the first set
     *
     * @param array $first
     * @param array $second
     * @throws QueryRewriteException
     */
    protected function _processLesser(&$first, &$second) {

        if (!isset($first['$lt'])) {
            return;
        }

        if (isset($second['$gt']) && $this->_isLesser($first['$lt'], $second['$gt'], false)) {
            throw new QueryRewriteException('Invalid condition');
        }

        if (isset($second['$gte']) && $this->_isLesser($first['$lt'], $second['$gte'], false)) {
            throw new QueryRewriteException('Invalid condition');
        }

        if (isset($second['$lt'])) {

            if ($this->_isGreater($first['$lt'], $second['$lt'])) {
                unset($first['$lt']);
                return;
            } else {
                unset($second['$lt']);
            }

        }

        if (isset($second['$lte'])) {

            if ($this->_isGreater($first['$lt'], $second['$lte'])) {
                unset($first['$lt']);
                return;
            } else {
                unset($second['$lte']);
            }

        }

    }

    /**
     * Process $lte operator from the first set
     *
     * @param array $first
     * @param array $second
     * @throws QueryRewriteException
     */
    protected function _processLesserOrEqual(&$first, &$second) {

        if (!isset($first['$lte'])) {
            return;
        }

        if (isset($second['$gt']) && $this->_isLesser($first['$lte'], $second['$gt'], false)) {
            throw new QueryRewriteException('Invalid condition');
        }

        if (isset($second['$gte']) && $this->_isLesser($first['$lte'], $second['$gte'])) {
            throw new QueryRewriteException('Invalid condition');
        }

        if (isset($second['$lt'])) {

            if ($this->_isGreater($first['$lte'], $second['$lt'], false)) {
                unset($first['$lte']);
                return;
            } else {
                unset($second['$lt']);
            }

        }

        if (isset($second['$lte'])) {

            if ($this->_isGreater($first['$lte'], $second['$lte'])) {
                unset($first['$lte']);
                return;
            } else {
                unset($second['$lte']);
            }

        }

        if (isset($second['$nin']) && in_array($first['$lte'], $second['$nin'])) {
            $first['$lt'] = $first['$lte'];
            unset($first['$lte']);
        }

    }

    /**
     * Process $in operator from the first set
     *
     * @param array $first
     * @param array $second
     * @throws QueryRewriteException
     */
    protected function _processIn(&$first, &$second) {

        if (!isset($first['$in'])) {
            return;
        }

        if (isset($second['$in'])) {
            $first['$in'] = array_intersect($first['$in'], $second['$in']);
        }

        if (isset($second['$nin'])) {
            $first['$in'] = array_diff($first['$in'], $second['$nin']);
        }

        if (isset($second['$gt'])) {
            foreach ($first['$in'] as $key => $value) {
                if ($this->_isGreater($second['$gt'], $value, false)) {
                    unset($first['$in'][$key]);
                }
            }
        }

        if (isset($second['$gte'])) {
            foreach ($first['$in'] as $key => $value) {
                if ($this->_isGreater($second['$gte'], $value)) {
                    unset($first['$in'][$key]);
                }
            }
        }

        if (isset($second['$lt'])) {
            foreach ($first['$in'] as $key => $value) {
                if ($this->_isGreater($value, $second['$lt'], false)) {
                    unset($first['$in'][$key]);
                }
            }
        }

        if (isset($second['$lte'])) {
            foreach ($first['$in'] as $key => $value) {
                if ($this->_isGreater($value, $second['$lte'])) {
                    unset($first['$in'][$key]);
                }
            }
        }

        // unset the rest of the fields
        unset($first['$nin']);
        unset($first['$gt']);
        unset($first['$gte']);
        unset($first['$lt']);
        unset($first['$lte']);
        unset($second['$in']);
        unset($second['$nin']);
        unset($second['$gt']);
        unset($second['$gte']);
        unset($second['$lt']);
        unset($second['$lte']);

        $first['$in'] = array_values($first['$in']);
        if (empty($first['$in'])) {
            throw new QueryRewriteException('Invalid condition');
        }

    }

    /**
     * Process $nin operator from the first set
     *
     * @param array $first
     * @param array $second
     * @throws QueryRewriteException
     */
    protected function _processNotIn(&$first, &$second) {

        if (!isset($first['$nin'])) {
            return;
        }

        if (isset($second['$nin'])) {
            $first['$nin'] = array_unique(array_merge($first['$nin'], $second['$nin']));
        }

        if (isset($second['$gt'])) {
            foreach ($first['$nin'] as $key => $value) {
                if ($this->_isGreater($second['$gt'], $value, false)) {
                    unset($first['$nin'][$key]);
                }
            }
        }

        if (isset($second['$gte'])) {

            $strict = false;
            foreach ($first['$nin'] as $key => $value) {
                if ($this->_isGreater($second['$gte'], $value)) {
                    unset($first['$nin'][$key]);
                } else if ($this->_isEqual($second['$gte'], $value)) {
                    $strict = true;
                }
            }

            if ($strict) {
                $second['$gt'] = $second['$gte'];
                unset($second['$gte']);
            }

        }

        if (isset($second['$lt'])) {
            foreach ($first['$nin'] as $key => $value) {
                if ($this->_isGreater($value, $second['$lt'], false)) {
                    unset($first['$nin'][$key]);
                }
            }
        }

        if (isset($second['$lte'])) {

            $strict = false;
            foreach ($first['$nin'] as $key => $value) {
                if ($this->_isGreater($value, $second['$lte'])) {
                    unset($first['$nin'][$key]);
                }
            }

            if ($strict) {
                $second['$lt'] = $second['$lte'];
                unset($second['$lte']);
            }

        }

        unset($second['$nin']);

        $first['$nin'] = array_values($first['$nin']);
        if (empty($first['$nin'])) {
            unset($first['$nin']);
        }

    }

} 