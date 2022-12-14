<?php declare(strict_types = 1);

namespace Bug8442;

use function PHPStan\Testing\assertType;
use DateInterval;

function () {
	assertType('false', DateInterval::createFromDateString('foo'));
	assertType('DateInterval', DateInterval::createFromDateString('1 Day'));

	if (rand(0,1)) {
		$interval = 'P1Y';
	} else {
		$interval = 'P2Y';
	}

	assertType('DateInterval', DateInterval::createFromDateString($interval));
};

