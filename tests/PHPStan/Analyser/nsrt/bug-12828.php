<?php declare(strict_types = 1);

namespace Bug12828;

use function PHPStan\Testing\assertType;

$a = ['abc' => 'def', 'hello' => 'world'];
assertType("array{abc: 'def', hello: 'world'}", $a);
$a = array_replace($a, ['hello' => 'country']);
assertType("array{abc: 'def', hello: 'country'}", $a);
