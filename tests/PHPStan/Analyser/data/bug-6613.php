<?php

namespace Bug6613;

use function PHPStan\Testing\assertType;

function (\DateTime $dt) {
	assertType("'000000'", date('u'));
	assertType('non-falsy-string', date_format($dt, 'u'));
	assertType('non-falsy-string', $dt->format('u'));
};
