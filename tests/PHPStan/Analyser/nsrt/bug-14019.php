<?php declare(strict_types = 1);

namespace Bug14019;

use function PHPStan\Testing\assertType;

[($a = 'foo') => $b] = ['foo' => 1];

assertType("'foo'", $a);
assertType('1', $b);
