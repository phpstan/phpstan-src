<?php declare(strict_types = 1);

use function PHPStan\Testing\assertType;

$a = [];
$a[4] = 1;
$a[] = 2;

assertType('array{4, 5}', array_keys($a));

$a = [];
$a[-4] = 1;
$a[] = 2;

assertType('array{-4, -3}', array_keys($a));

$a = [];
$a[-4] = 1;
$a[-2] = 1;
$a[] = 2;

assertType('array{-4, -2, -1}', array_keys($a));

$a = [];
$a[-3] = 1;
$a[-4] = 1;
$a[-2] = 1;
$a[] = 2;

assertType('array{-3, -4, -2, -1}', array_keys($a));

$a = [];
$a[-1] = 1;
$a[] = 2;

assertType('array{-1, 0}', array_keys($a));

$a = [];
$a[0] = 1;
$a[] = 2;

assertType('array{0, 1}', array_keys($a));
