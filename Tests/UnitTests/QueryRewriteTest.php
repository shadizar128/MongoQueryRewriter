<?php
use Lib\Mongo\QueryRewrite\CollisionHandler;
use Lib\Mongo\QueryRewrite\QueryRewrite;


/**
 * @backupGlobals disabled
 */
class QueryRewriteTest  extends \PHPUnit_Framework_TestCase {

    /**
     * @var CollisionHandler
     */
    protected $_collisionHandler;

    /**
     * @var QueryRewrite
     */
    protected $_queryRewrite;

    /**
     * Setup phase
     */
    protected function setUp() {
        $this->_queryRewrite = new QueryRewrite(new CollisionHandler());
    }

    public function test_1() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$or' => array(
                array('a' => 1),
                array('a' => 2)
            ),
            'a' => 3
        ), $options);

        $this->assertEquals($result, array('_id' => null));

    }

    public function test_2() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$or' => array(
                array('a' => 1),
                array('a' => 2),
                array('b' => 2)
            ),
            'a' => 3
        ), $options);

        $this->assertEquals($result, array(
            'a' => 3,
            'b' => 2
        ));

    }

    public function test_3() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$or' => array(
                array('a' => 1),
                array('b' => 2)
            ),
            '$and' => array(
                array('a' => 3),
                array('b' => 2)
            )
        ), $options);

        $this->assertEquals($result, array('a' => 3, 'b' => 2));

    }

    public function test_4() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$or' => array(
                array('a' => 1),
                array('b' => 2)
            ),
            '$and' => array(
                array('a' => 3),
                array('b' => 5)
            )
        ), $options);

        $this->assertEquals($result, array('_id' => null));

    }

    public function test_5() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$or' => array(
                array('a' => array('$gt' => 1)),
                array('b' => array('$gt' => 3))
            ),
            '$and' => array(
                array('a' => 3),
                array('b' => 5)
            )
        ), $options);

        $this->assertEquals($result, array('b' => 5, 'a' => 3));

    }

    public function test_6() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$or' => array(
                array('a' => array('$gt' => 1)),
                array('b' => array('$gt' => 3))
            ),
            '$and' => array(
                array('a' => 3),
                array('b' => array('$lt' => 10))
            )
        ), $options);

        $this->assertEquals($result, array(
            '$or' => array(
                array('b' => array('$lt' => 10), 'a' => 3),
                array('a' => 3, 'b' => array('$gt' => 3, '$lt' => 10)),
            )
        ));

    }

    public function test_7() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$or' => array(
                array('a' => array('$gt' => 1)),
                array('b' => 10)
            ),
            '$and' => array(
                array(
                    '$or' => array(
                        array('c' => array('$gt' => 1)),
                        array('d' => 3)
                    )
                ),
                array(
                    '$or' => array(
                        array('a' => array('$gt' => 5)),
                        array('d' => array('$lte' => 3))
                    )
                ),
            )
        ), $options);

        $this->assertEquals($result, array(
            '$or' => array(
                array('c' => array('$gt' => 1), 'a' => array('$gt' => 5)),
                array('a' => array('$gt' => 1), 'c' => array('$gt' => 1), 'd' => array('$lte' => 3)),
                array('d' => 3, 'a' => array('$gt' => 5)),
                array('a' => array('$gt' => 1), 'd' => 3),
                array('b' => 10, 'c' => array('$gt' => 1), 'a' => array('$gt' => 5)),
                array('b' => 10, 'c' => array('$gt' => 1), 'd' => array('$lte' => 3)),
                array('b' => 10, 'd' => 3, 'a' => array('$gt' => 5)),
                array('b' => 10,'d' => 3)
            )
        ));

    }

    public function test_8() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$and' => array(
                array(
                    '$or' => array(
                        array('a' => array('$gt' => 1), 'b' => array('$in' => array(1, 3, 5))),
                        array('c' => 3),
                        array('c' => array('$gt' => 2))
                    )
                ),
                array(
                    '$or' => array(
                        array('a' => array('$gt' => 5), 'c' => array('$nin' => array(5))),
                        array('b' => array('$lte' => 3))
                    )
                ),
            )
        ), $options);

        $this->assertEquals($result, array(
            '$or' => array(
                array('b' => array('$in' => array(1, 3, 5)), 'c' => array('$ne' => 5), 'a' => array('$gt' => 5)),
                array('a' => array('$gt' => 1), 'b' => array('$in' => array(1, 3))),
                array('a' => array('$gt' => 5), 'c' => 3),
                array('c' => 3, 'b' => array('$lte' => 3)),
                array('a' => array('$gt' => 5), 'c' => array('$gt' => 2, '$ne' => 5)),
                array('b' => array('$lte' => 3), 'c' => array('$gt' => 2))
            )
        ));

    }

    public function test_9() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$and' => array(
                array(
                    '$or' => array(
                        array('a' => array('$gt' => 5), 'b' => array('$in' => array(3, 5))),
                        array('a' => 3, 'c' => 5),
                    )
                ),
                array(
                    '$or' => array(
                        array('a' => array('$lte' => 5), 'b' => array('$nin' => array(3, 4)), 'c' => 3),
                        array('c' => array('$gt' => 5), 'b' => array('$nin' => array(3, 5)))
                    )
                ),
            )
        ), $options);

        $this->assertEquals($result, array('_id' => null));

    }

    public function test_10() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            '$and' => array(
                array(
                    '$or' => array(
                        array('a' => array('$gt' => 5), 'b' => array('$in' => array(3, 5))),
                        array('a' => 3, 'c' => 5),
                    )
                ),
                array(
                    '$or' => array(
                        array('a' => array('$lte' => 5), 'b' => array('$nin' => array(3, 4)), 'c' => 3),
                        array('c' => array('$gt' => 5), 'b' => array('$nin' => array(3, 5))),
                        array('a' => 3)
                    )
                ),
            )
        ), $options);

        $this->assertEquals($result, array('a' => 3, 'c' => 5));

    }

    public function test_11() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            'a' => array(
                '$gt' => 5,
                '$gte' => 7,
                '$lte' => 2,
                '$lt' => 3
            )
        ), $options);

        $this->assertEquals($result, array('_id' => null));

    }

    public function test_12() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            'a' => array(
                '$gt' => 5,
                '$gte' => 7,
                '$lte' => 13,
                '$lt' => 12
            )
        ), $options);

        $this->assertEquals($result, array('a' => array('$gte' => 7, '$lt' => 12)));

    }

    public function test_13() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            'a' => array(
                '$in' => array(3),
                '$nin' => array(7)
            )
        ), $options);

        $this->assertEquals($result, array('a' => 3));

    }

    public function test_14() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            'a' => array(
                '$in' => array(3),
                '$nin' => array(3)
            )
        ), $options);

        $this->assertEquals($result, array('_id' => null));

    }

    public function test_15() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            'a' => new \MongoId("563cb7c9b1a43d7c51f05d15")
        ), $options);

        $this->assertEquals($result, array('a' => new \MongoId("563cb7c9b1a43d7c51f05d15")));

    }

    public function test_16() {

        $options = array('onFailure' => QueryRewrite::ON_FAILURE_RETURN_IMPOSSIBLE_QUERY);
        $result = $this->_queryRewrite->rewrite(array(
            'a' => 3,
            'b' => 5
        ), $options);

        $this->assertEquals($result, array('a' => 3, 'b' => 5));

    }

}