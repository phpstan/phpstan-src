<?php

namespace Analyser\Bug2750;

use function PHPStan\Analyser\assertType;

function (array $input) {
	\assert(count($input) > 0);
	assertType('int<1, max>', count($input));
	array_shift($input);
	assertType('int', count($input));

	\assert(count($input) > 0);
	assertType('int<1, max>', count($input));
	array_pop($input);
	assertType('int', count($input));

	\assert(count($input) > 0);
	assertType('int<1, max>', count($input));
	array_unshift($input, 'test');
	assertType('int', count($input));

	\assert(count($input) > 0);
	assertType('int<1, max>', count($input));
	array_push($input, 'nope');
	assertType('int', count($input));
};
