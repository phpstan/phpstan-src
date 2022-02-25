<?php

namespace CallableInUnion;

use function PHPStan\Testing\assertType;

/** @param array<string, mixed>|(callable(array<string, mixed>): array<string, mixed>) $_ */
function acceptArrayOrCallable($_)
{
}

acceptArrayOrCallable(fn ($parameter) => assertType('array<string, mixed>', $parameter));

acceptArrayOrCallable(function ($parameter) {
	assertType('array<string, mixed>', $parameter);
	return $parameter;
});
