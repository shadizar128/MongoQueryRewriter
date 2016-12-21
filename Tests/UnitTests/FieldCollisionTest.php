<?php
use Lib\Mongo\QueryRewrite\CollisionHandler;

/**
 * @backupGlobals disabled
 */
class CollisionHandlerTest extends PHPUnit_Framework_TestCase {

    /**
     * @var CollisionHandler
     */
    protected $_collisionHandler;

    /**
     * Setup phase
     */
    protected function setUp() {
        $this->_collisionHandler = new CollisionHandler();
    }

    public function test_1() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$gte' => 5, '$lt' => 6, '$lte' => 6),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5, '$lt' => 6));

    }
    public function test_2() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$lte' => 5),
            array()
        );

        $this->assertEquals($result, array('$gte' => 5, '$lte' => 5));

    }
    public function test_3() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 6, '$lte' => 5),
            array()
        );

    }
    public function test_4() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$lte' => 6),
            array()
        );

        $this->assertEquals($result, array('$gte' => 5, '$lte' => 6));

    }
    public function test_5() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$lte' => 5),
            array()
        );

    }
    public function test_6() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 6, '$lte' => 5),
            array()
        );

    }
    public function test_7() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$lte' => 6),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5, '$lte' => 6));

    }
    public function test_8() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$lt' => 5),
            array()
        );

    }
    public function test_9() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 6, '$lt' => 5),
            array()
        );

    }
    public function test_10() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$lt' => 6),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5, '$lt' => 6));

    }
    public function test_11() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$lt' => 5),
            array()
        );

    }
    public function test_12() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 6, '$lt' => 5),
            array()
        );

    }
    public function test_13() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$lt' => 6),
            array()
        );

        $this->assertEquals($result, array('$gte' => 5, '$lt' => 6));

    }
    public function test_14() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$in' => [1, 2, 3, 4]),
            array()
        );

    }
    public function test_15() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$in' => [1, 2, 3, 4, 5]),
            array()
        );

        $this->assertEquals($result, array('$in' => [5]));

    }
    public function test_16() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$in' => [1, 2, 3, 4, 5, 6]),
            array()
        );

        $this->assertEquals($result, array('$in' => [5, 6]));

    }
    public function test_17() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$in' => [6, 7]),
            array()
        );

        $this->assertEquals($result, array('$in' => [6, 7]));

    }
    public function test_18() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$in' => [1, 2, 3, 4]),
            array()
        );

    }
    public function test_19() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$in' => [1, 2, 3, 4, 5]),
            array()
        );

    }
    public function test_20() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$in' => [1, 2, 3, 4, 5, 6]),
            array()
        );

        $this->assertEquals($result, array('$in' => [6]));

    }
    public function test_21() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$in' => [6, 7]),
            array()
        );

        $this->assertEquals($result, array('$in' => [6, 7]));

    }
    public function test_22() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lte' => 5, '$in' => [1, 2, 3, 4]),
            array()
        );

        $this->assertEquals($result, array('$in' => [1, 2, 3, 4]));

    }
    public function test_23() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lte' => 5, '$in' => [1, 2, 3, 4, 5]),
            array()
        );

        $this->assertEquals($result, array('$in' => [1, 2, 3, 4, 5]));

    }
    public function test_24() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lte' => 5, '$in' => [1, 2, 3, 4, 5, 6]),
            array()
        );

        $this->assertEquals($result, array('$in' => [1, 2, 3, 4, 5]));

    }
    public function test_25() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$lte' => 5, '$in' => [6, 7]),
            array()
        );

    }
    public function test_26() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lt' => 5, '$in' => [1, 2, 3, 4]),
            array()
        );

        $this->assertEquals($result, array('$in' => [1, 2, 3, 4]));

    }
    public function test_27() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lt' => 5, '$in' => [1, 2, 3, 4, 5]),
            array()
        );

        $this->assertEquals($result, array('$in' => [1, 2, 3, 4]));

    }
    public function test_28() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lt' => 5, '$in' => [1, 2, 3, 4, 5, 6]),
            array()
        );

        $this->assertEquals($result, array('$in' => [1, 2, 3, 4]));

    }
    public function test_29() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$lt' => 5, '$in' => [6, 7]),
            array()
        );

    }
    public function test_30() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$nin' => [1, 2, 3, 4]),
            array()
        );

        $this->assertEquals($result, array('$gte' => 5));

    }
    public function test_31() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$nin' => [1, 2, 3, 4, 5]),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5));

    }
    public function test_32() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$nin' => [1, 2, 3, 4, 5, 6, 7]),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5, '$nin' => [6, 7]));

    }
    public function test_33() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gte' => 5, '$nin' => [6, 7]),
            array()
        );

        $this->assertEquals($result, array('$gte' => 5, '$nin' => [6, 7]));

    }
    public function test_34() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$nin' => [1, 2, 3, 4]),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5));

    }
    public function test_35() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$nin' => [1, 2, 3, 4, 5]),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5));

    }
    public function test_36() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$nin' => [1, 2, 3, 4, 5, 6]),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5, '$nin' => [6]));

    }
    public function test_37() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => 5, '$nin' => [6, 7]),
            array()
        );

        $this->assertEquals($result, array('$gt' => 5, '$nin' => [6, 7]));

    }
    public function test_38() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lte' => 5, '$nin' => [1, 2, 3, 4]),
            array()
        );

        $this->assertEquals($result, array('$lte' => 5, '$nin' => [1, 2, 3, 4]));

    }
    public function test_39() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lte' => 5, '$nin' => [1, 2, 3, 4, 5]),
            array()
        );

        $this->assertEquals($result, array('$lt' => 5, '$nin' => [1, 2, 3, 4]));

    }
    public function test_40() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lte' => 5, '$nin' => [1, 2, 3, 4, 5, 6]),
            array()
        );

        $this->assertEquals($result, array('$lt' => 5, '$nin' => [1, 2, 3, 4]));

    }
    public function test_41() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lte' => 5, '$nin' => [6, 7]),
            array()
        );

        $this->assertEquals($result, array('$lte' => 5));

    }
    public function test_42() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lt' => 5, '$nin' => [1, 2, 3, 4]),
            array()
        );

        $this->assertEquals($result, array('$lt' => 5, '$nin' => [1, 2, 3, 4]));

    }
    public function test_43() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lt' => 5, '$nin' => [1, 2, 3, 4, 5]),
            array()
        );

        $this->assertEquals($result, array('$lt' => 5, '$nin' => [1, 2, 3, 4]));

    }
    public function test_44() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lt' => 5, '$nin' => [1, 2, 3, 4, 5, 6]),
            array()
        );

        $this->assertEquals($result, array('$lt' => 5, '$nin' => [1, 2, 3, 4]));

    }
    public function test_45() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$lt' => 5, '$nin' => [6, 7]),
            array()
        );

        $this->assertEquals($result, array('$lt' => 5));

    }
    public function test_46() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            array('$in' => [1, 2], '$nin' => [1, 2]),
            array()
        );

    }
    public function test_47() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$in' => [1, 2, 3], '$nin' => [1, 2]),
            array()
        );

        $this->assertEquals($result, array('$in' => [3]));

    }

    public function test_48() {

        $value = new \MongoDate(time(), 0);
        $result = $this->_collisionHandler->handleFieldCollision(
            $value,
            $value
        );

        $this->assertEquals($result, $value);

    }

    public function test_49() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            new \MongoDate(10000001, 0),
            new \MongoDate(10000000, 0)
        );

    }

    public function test_50() {

        $result = $this->_collisionHandler->handleFieldCollision(
            new \MongoId("563cb7c9b1a43d7c51f05d15"),
            new \MongoId('563cb7c9b1a43d7c51f05d15')
        );

        $this->assertEquals($result, new \MongoId('563cb7c9b1a43d7c51f05d15'));

    }

    public function test_51() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');
        $this->_collisionHandler->handleFieldCollision(
            new \MongoId('563cb7c9b1a43d7c51f05d15'),
            new \MongoId('563cb7c9b1a43d7c51f05d16')
        );

    }

    public function test_52() {

        $value = 3;
        $result = $this->_collisionHandler->handleFieldCollision(
            $value,
            $value
        );

        $this->assertEquals($result, $value);

    }

    public function test_53() {

        $this->expectException('Exceptions\QueryRewriteException');
        $this->expectExceptionMessage('Could not rewrite query');

        $value1 = 3;
        $value2 = 4;
        $this->_collisionHandler->handleFieldCollision(
            $value1,
            $value2
        );

    }

    public function test_54() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => new \MongoDate(10000000, 0)),
            array('$gt' => new \MongoDate(10000001, 0))
        );

        $this->assertEquals($result, array('$gt' => new \MongoDate(10000001, 0)));

    }

    public function test_55() {

        $result = $this->_collisionHandler->handleFieldCollision(
            array('$gt' => new \MongoDate(10000001, 0)),
            array('$gt' => new \MongoDate(10000000, 0))
        );

        $this->assertEquals($result, array('$gt' => new \MongoDate(10000001, 0)));

    }

}