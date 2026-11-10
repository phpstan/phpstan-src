<?php declare(strict_types = 1);

namespace Bug15364;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

$makeSlug = str_replace(' ', '-', ?);
