<?php

use function PHPStan\Testing\assertType;

/** @var string|null $foo */
$foo = null;
/** @var int|null $bar */
$bar = null;

if ((new \PHPStan\Tests\AssertionClass())->assertString($foo) && \PHPStan\Tests\AssertionClass::assertInt($bar)) {
	assertType('string|null', $foo);
	assertType('int|null', $bar);
}
