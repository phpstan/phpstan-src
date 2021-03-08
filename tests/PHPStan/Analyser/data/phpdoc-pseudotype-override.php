<?php

use Foo\Number;
use Foo\Numeric;
use Foo\Boolean;
use Foo\Resource;
use Foo\Never;
use Foo\Double;

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

	assertType('Foo\Number', $number);
	assertType('Foo\Numeric', $numeric);
	assertType('Foo\Boolean', $boolean);
	assertType('Foo\Resource', $resource);
	assertType('Foo\Never', $never);
	assertType('Foo\Double', $double);
};
