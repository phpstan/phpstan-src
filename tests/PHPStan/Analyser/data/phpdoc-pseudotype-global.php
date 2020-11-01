<?php

use function PHPStan\Analyser\assertType;

function () {
	/** @var Number $number */
	$number = doFoo();

	/** @var Boolean $boolean */
	$boolean = doFoo();

	/** @var Numeric $numeric */
	$numeric = doFoo();

	/** @var Never $never */
	$never = doFoo();

	/** @var Resource $resource */
	$resource = doFoo();

	/** @var Double $double */
	$double = doFoo();

	assertType('float|int', $number);
	assertType('float|int|(string&numeric)', $numeric);
	assertType('bool', $boolean);
	assertType('resource', $resource);
	assertType('*NEVER*', $never);
	assertType('float', $double);
};
