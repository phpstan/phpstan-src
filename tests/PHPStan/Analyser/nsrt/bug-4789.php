<?php

namespace Bug4789;

use function PHPStan\Testing\assertType;

function doFoo(\DatePeriod $p) {
	foreach ($p as $dt) {
		assertType('DateTimeInterface', $dt);
	}
}
