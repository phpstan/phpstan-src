<?php

namespace PhpdocPseudoTypesNamespace;

use function PHPStan\Testing\assertType;

class Number {}
class Numeric {}
class Boolean {}
class Resource {}
class Never {}
class Double {}

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

	assertType('PhpdocPseudoTypesNamespace\Number', $number);
	assertType('PhpdocPseudoTypesNamespace\Numeric', $numeric);
	assertType('PhpdocPseudoTypesNamespace\Boolean', $boolean);
	assertType('PhpdocPseudoTypesNamespace\Resource', $resource);
	assertType('PhpdocPseudoTypesNamespace\Never', $never);
	assertType('PhpdocPseudoTypesNamespace\Double', $double);
};
