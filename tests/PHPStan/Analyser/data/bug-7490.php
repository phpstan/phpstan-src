<?php declare(strict_types=1);

namespace Bug7490;

use function PHPStan\Testing\assertType;

function () {
	assertType('*ERROR*', 1 << -1);
	assertType('*ERROR*', 1 >> -1);
};
