<?php

declare(strict_types = 1);

namespace Bug5009;

use Closure;
use function PHPStan\Testing\assertType;

$foo = function (): void {};
$bar = $foo->bindTo(null);
assertType('Closure(): void', $bar);

$baz = Closure::bind($foo, null);
assertType('Closure(): void', $baz);

$newThis = new \stdClass();
$bound = $foo->bindTo($newThis);
assertType('((Closure(): void)|null)', $bound);

$staticBound = Closure::bind($foo, $newThis);
assertType('((Closure(): void)|null)', $staticBound);

$bound2 = $foo->bindTo($newThis, 'stdClass');
assertType('((Closure(): void)|null)', $bound2);

$static = static function (): void {};
$boundStatic = $static->bindTo($newThis);
assertType('((Closure(): void)|null)', $boundStatic);

$boundStaticNull = $static->bindTo(null);
assertType('Closure(): void', $boundStaticNull);

/** @var \stdClass|null $maybeNull */
$maybeNull = null;
$boundMaybe = $foo->bindTo($maybeNull);
assertType('((Closure(): void)|null)', $boundMaybe);
