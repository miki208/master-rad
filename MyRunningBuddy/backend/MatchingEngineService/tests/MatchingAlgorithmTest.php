<?php

use Laravel\Lumen\Testing\DatabaseMigrations;

class MatchingAlgorithmTest extends TestCase
{
    use DatabaseMigrations;

    public function testScoringFunction()
    {
        $method = self::getPrivateMethod('App\Http\Controllers\MatcherController', 'scoring_function');

        // same normalized values should result in maximized score
        $this->assertEquals(1, $method->invoke(null, 1, 1));
        $this->assertEquals(1, $method->invoke(null, 0.3, 0.3));
        $this->assertEquals(1, $method->invoke(null, 1.1, 1.1));

        // very different normalized values should result in very low score
        $this->assertEquals(0.2, $method->invoke(null, 0.9, 0.1));

        // score function should be commutative
        $this->assertTrue($method->invoke(null, 0.6, 0.4) === $method->invoke(null, 0.4, 0.6));

        // it also should work for values out of range [0, 1]
        $this->assertEquals(-1.9, $method->invoke(null, 1.5, -1.4));
    }

    public function testNormalizationFunction()
    {
        $method = self::getPrivateMethod('App\Http\Controllers\MatcherController', 'normalize_field_value');

        // null value should result in average normalized value (0.5)
        $this->assertEquals(0.5, $method->invoke(null, null, 1, 10));

        // value in the middle of range should also give result of 0.5
        $this->assertEquals(0.5, $method->invoke(null, 5, 0, 10));

        // values at the limits of the range should give 0 and 1 respectively
        $this->assertEquals(0, $method->invoke(null, 6, 6, 12));
        $this->assertEquals(1, $method->invoke(null, 12, 6, 12));

        // it should also be possible to get scores lower than 0 and bigger than 1 if the value is out of the defined range
        $this->assertTrue($method->invoke(null, 5, 6, 12) < 0);
        $this->assertTrue($method->invoke(null, 13, 6, 12) > 1);
    }

    private static function getPrivateMethod($class_name, $name) {
        $class = new ReflectionClass($class_name);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
