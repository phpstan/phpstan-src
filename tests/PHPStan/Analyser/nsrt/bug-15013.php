<?php declare(strict_types = 1);

namespace Bug15013;

use function PHPStan\Testing\assertType;

function () {
	$string = 'App/Service::foo';

	assertType("array{'App/Service::foo'}", explode(':::', $string));

	[$first, $second] = explode(':::', $string);

	assertType("'App/Service::foo'", $first);
	assertType('*ERROR*', $second);
};
