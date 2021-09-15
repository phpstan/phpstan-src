<?php

use function PHPStan\Testing\assertType;

/** @var string|null $foo */
$foo = null;
/** @var int|null $bar */
$bar = null;

(new \PHPStan\Tests\AssertionClass())->assertString($foo);
$test = \PHPStan\Tests\AssertionClass::assertInt($bar);

assertType('string|null', $foo);
assertType('int|null', $bar);
