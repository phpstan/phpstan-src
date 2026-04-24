<?php

namespace ForeachArrayRewrite;

use function PHPStan\Testing\assertType;

interface Thing
{
}

class Resolver
{
	public function resolve(string $s): Thing
	{
		throw new \Exception();
	}
}

class Tester
{

	/**
	 * @param list<Thing>|list<string> $types
	 */
	public function narrowAndReplace(array $types, Resolver $resolver): void
	{
		foreach ($types as $i => $type) {
			if (!is_string($type)) {
				continue;
			}

			$types[$i] = $resolver->resolve($type);
		}

		// Every iteration ends with $types[$i] being a Thing — either the continue
		// branch (where $type was already a Thing, so $types[$i] was too) or the
		// assignment branch (where $types[$i] was overwritten with a Thing).
		assertType('list<ForeachArrayRewrite\\Thing>', $types);
	}

	/**
	 * @param list<Thing|string> $types
	 */
	public function reassignValueVarIsNotAliased(array $types): void
	{
		foreach ($types as $i => $type) {
			// Reassigning $type does not modify $types[$i], so the array's element
			// type must be preserved (not narrowed to 'foo').
			$type = 'foo';
		}

		assertType('list<ForeachArrayRewrite\\Thing|string>', $types);
	}

	/**
	 * @param list<Thing|string> $types
	 */
	public function plainNarrowingFlowsThrough(array $types): void
	{
		foreach ($types as $i => $type) {
			if (is_string($type)) {
				continue;
			}
		}

		// The loop didn't modify $types, so its shape is unchanged.
		assertType('list<ForeachArrayRewrite\\Thing|string>', $types);
	}

}
