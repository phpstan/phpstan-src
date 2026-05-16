<?php

declare(strict_types = 1);

namespace Bug5009;

use Closure;
use function PHPStan\Testing\assertType;

$foo = function (): void {};
$bar = $foo->bindTo(null);
assertType('((Closure(): void)|null)', $bar);

$baz = Closure::bind($foo, null);
assertType('((Closure(): void)|null)', $baz);

$newThis = new \stdClass();
$bound = $foo->bindTo($newThis);
assertType('((Closure(): void)|null)', $bound);

$staticBound = Closure::bind($foo, $newThis);
assertType('((Closure(): void)|null)', $staticBound);

$bound2 = $foo->bindTo($newThis, 'stdClass');
assertType('((Closure(): void)|null)', $bound2);
