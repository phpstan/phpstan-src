<?php // lint >= 8.3

namespace Bug8442Php83;

use stdClass;
use function PHPStan\Testing\assertType;
use DateInterval;

function () {
	assertType('*NEVER*', DateInterval::createFromDateString('foo'));
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

	assertType('DateInterval', DateInterval::createFromDateString($interval));

	if (rand(0,1)) {
		$interval = 'foo';
	} else {
		$interval = 'foo';
	}

	assertType('*NEVER*', DateInterval::createFromDateString($interval));

	assertType('DateInterval',DateInterval::createFromDateString(str_shuffle('1 day')));
	assertType('DateInterval',DateInterval::createFromDateString());
	assertType('DateInterval',DateInterval::createFromDateString(new stdClass()));
};
