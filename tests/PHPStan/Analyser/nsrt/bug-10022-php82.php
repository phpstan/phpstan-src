<?php // lint < 8.3

namespace Bug10022Php82;

use function PHPStan\Testing\assertType;

// before PHP 8.3 a numeric string boundary was implicitly cast to int
assertType('array{1, 0}', range('1', 'a'));

// the sign of the step used to be ignored
assertType('array{2, 3, 4, 5}', range(2, 5, -1));

// a float step with no fractional part used to produce floats
assertType('non-empty-list<float>', range(1, 200, 1.0));
