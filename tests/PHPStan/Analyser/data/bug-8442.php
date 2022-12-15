<?php declare(strict_types = 1);

namespace Bug8442;

use function PHPStan\Testing\assertType;
use DateInterval;

function () {
	assertType('false', DateInterval::createFromDateString('foo'));
	assertType('DateInterval', DateInterval::createFromDateString('1 Day'));

	if (rand(0,1)) {
		$interval = '1 day';
	} else {
		$interval = '2 day';
	}

	assertType('DateInterval', DateInterval::createFromDateString($interval));

	if (rand(0,1)) {
		$interval = 'foo';
	} else {
		$interval = '2 day';
	}

	assertType('DateInterval|false', DateInterval::createFromDateString($interval));

	if (rand(0,1)) {
		$interval = 'foo';
	} else {
		$interval = 'foo';
	}

	assertType('false', DateInterval::createFromDateString($interval));

	DateInterval::createFromDateString();
};

