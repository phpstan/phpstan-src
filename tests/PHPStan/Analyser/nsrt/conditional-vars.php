<?php declare(strict_types = 1);

namespace ConditionalVars;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param array<mixed> $innerHits */
	public function conditionalVarInTernary(array $innerHits): void
	{
		if (array_key_exists('nearest_premise', $innerHits) || array_key_exists('matching_premises', $innerHits)) {
			assertType('non-empty-array', $innerHits);
			$x = array_key_exists('nearest_premise', $innerHits)
				? assertType("non-empty-array&hasOffset('nearest_premise')", $innerHits)
				: assertType('non-empty-array', $innerHits);

			assertType('non-empty-array', $innerHits);
		}
		assertType('array', $innerHits);
	}

	/** @param array<mixed> $innerHits */
	public function conditionalVarInIf(array $innerHits): void
	{
		if (array_key_exists('nearest_premise', $innerHits) || array_key_exists('matching_premises', $innerHits)) {
			assertType('non-empty-array', $innerHits);
			if (array_key_exists('nearest_premise', $innerHits)) {
				assertType("non-empty-array&hasOffset('nearest_premise')", $innerHits);
			} else {
				assertType('non-empty-array', $innerHits);
			}

			assertType('non-empty-array', $innerHits);
		}
		assertType('array', $innerHits);
	}
}
