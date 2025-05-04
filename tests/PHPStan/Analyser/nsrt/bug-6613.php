<?php

namespace Bug6613;

use function PHPStan\Testing\assertType;

function (\DateTime $dt) {
	assertType("'000000'", date('u'));
	assertType('numeric-string', date_format($dt, 'u'));
	assertType('numeric-string', $dt->format('u'));

	assertType("'000'", date('v'));
	assertType('numeric-string', date_format($dt, 'v'));
	assertType('numeric-string', $dt->format('v'));
};
