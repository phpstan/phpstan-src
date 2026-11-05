<?php

namespace ConcatBenevolentUnion;

use function PHPStan\Testing\assertType;

/** @param mixed $mixed */
function foo($mixed, object $object): void
{
	assertType('non-falsy-string', 'attribute/' . str_replace('pa_', '', $mixed));
	assertType('non-falsy-string', 'attribute/' . str_replace('pa_', '', $object->slug));

	/** @var array<string>|string $arrayOrString */
	$arrayOrString = $mixed;
	assertType('*ERROR*', 'attribute/' . $arrayOrString);
}
