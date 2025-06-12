<?php

use function PHPStan\Testing\assertType;

/** @var string|null $foo */
$foo = null;

/** @var int|null $bar */
$bar = null;

(new \PHPStan\Tests\AssertionClass())->assertString($foo);
\PHPStan\Tests\AssertionClass::assertInt($bar);

assertType('non-empty-string', $foo);
assertType('int<1, max>', $bar);

