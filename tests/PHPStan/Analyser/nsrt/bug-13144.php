<?php

namespace Bug13144;

use ArrayObject;

use function PHPStan\Testing\assertType;

$arr = new ArrayObject(['a' => 1, 'b' => 2]);

assertType('ArrayObject<string, int>', $arr); // correctly inferred as `ArrayObject<string, int>`

$a = $arr['a']; // ok
$b = $arr['b']; // ok

assertType('int|null', $a); // correctly inferred as `int|null`
assertType('int|null', $b); // correctly inferred as `int|null`


['a' => $a, 'b' => $b] = $arr; // ok

assertType('int|null', $a); // incorrectly inferred as `mixed`
assertType('int|null', $b); // incorrectly inferred as `mixed`
