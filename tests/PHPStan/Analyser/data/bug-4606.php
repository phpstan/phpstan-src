<?php

namespace Bug4606;

use function PHPStan\Analyser\assertType;

/**
 * @var Foo $this
 * @var array $assigned
 * @phpstan-var list<array{\stdClass, int}> $assigned
 */

assertType(Foo::class, $this);
assertType('array<int, array(stdClass, int)>', $assigned);


/**
 * @var array
 * @phpstan-var array{\stdClass, int}
 */
$foo = doFoo();

assertType('array(stdClass, int)', $foo);
