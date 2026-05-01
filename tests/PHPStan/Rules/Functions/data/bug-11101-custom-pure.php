<?php

namespace Bug11101CustomPure;

/**
 * @phpstan-pure
 * @param array<int> $array
 * @param callable(int): bool $callback
 * @param-immediately-invoked-callable $callback
 * @return array<int>
 */
function pureWithImmediateCallback(array $array, callable $callback): array
{
	return array_filter($array, $callback);
}

/**
 * @phpstan-pure
 * @param array<int> $array
 * @param callable(int): bool $callback
 * @param-later-invoked-callable $callback
 * @return array<int>
 */
function pureWithLaterCallback(array $array, callable $callback): array
{
	return $array;
}

class Foo
{

	/**
	 * @param array<int> $array
	 */
	public function testImmediateCallback(array $array, callable $callback): void
	{
		// Pure callback - should be reported
		pureWithImmediateCallback($array, fn ($v) => $v > 5);

		// Impure callback - should NOT be reported
		pureWithImmediateCallback($array, function ($v) {
			echo $v;
			return $v > 0;
		});

		// Unknown callback - should NOT be reported
		pureWithImmediateCallback($array, $callback);
	}

	/**
	 * @param array<int> $array
	 */
	public function testLaterCallback(array $array, callable $callback): void
	{
		// Pure callback - should be reported
		pureWithLaterCallback($array, fn ($v) => $v > 5);

		// Impure callback - should be reported (later-invoked, callback impurity doesn't matter at call site)
		pureWithLaterCallback($array, function ($v) {
			echo $v;
			return $v > 0;
		});

		// Unknown callback - should be reported (later-invoked, callback impurity doesn't matter at call site)
		pureWithLaterCallback($array, $callback);
	}

}
