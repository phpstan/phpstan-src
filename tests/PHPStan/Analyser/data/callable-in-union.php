<?php // onlyif PHP_VERSION_ID >= 70400

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

/**
 * @param (callable(string): void)|callable(int): void $a
 * @return void
 */
function acceptCallableOrCallableLikeArray($a): void
{

}

acceptCallableOrCallableLikeArray(function ($p) {
	assertType('int|string', $p);
});

acceptCallableOrCallableLikeArray(fn ($p) => assertType('int|string', $p));
