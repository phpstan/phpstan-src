<?php

namespace Bug13097;

use function PHPStan\Testing\assertType;

$f = -1 * INF;
assertType('-INF', $f);

$f = 0 * INF;
assertType('NAN', $f);

$f = 1 * INF;
assertType('INF', $f);
