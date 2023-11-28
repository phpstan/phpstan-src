<?php

namespace Bug8659;

function acceptsString(string $input): string
{
	return $input;
}

function foo1(?string $bar, ?string $baz): string
{
	assert($bar !== null || $baz !== null);

	// In this case, baz cannot be null (because $bar is and assert above makes sure that at least one value is not-null. Yet phpstan fails.
	return $bar ?? acceptsString($baz);
}


function foo2(?string $bar, ?string $baz): string
{
	assert($bar !== null || $baz !== null);

	// In this case, PHPStan can resolve that $baz must be string and is OK.
	return $bar ?? $baz;
}

function doBar() {
	echo foo1(null, 'a');
	echo foo2(null, 'a');
}
