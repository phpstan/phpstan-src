<?php declare(strict_types = 1);

namespace UnusedVariableCatch;

function doFoo(): void
{
	try {
		doThrow();
	} catch (\Exception $e) {
	}
}

/** @phpstan-impure */
function doThrow(): void
{
}
