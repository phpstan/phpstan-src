<?php

namespace Bug7076;

use function PHPStan\Testing\assertType;

/**
 * @param array<int|string, mixed> $arguments
 * @return array<string, mixed>
 */
function narrowWithIsString(array $arguments): array
{
	foreach ($arguments as $key => $argument) {
		if (!is_string($key)) {
			throw new \Exception('Key must be a string');
		}
	}

	assertType('array<string, mixed>', $arguments);

	return $arguments;
}

/**
 * @param array<int|string, mixed> $arguments
 * @return array<string, mixed>
 */
function narrowWithIsInt(array $arguments): array
{
	foreach ($arguments as $key => $argument) {
		if (is_int($key)) {
			throw new \Exception('Key must be a string');
		}
	}

	assertType('array<string, mixed>', $arguments);

	return $arguments;
}

/**
 * @param array<int|string, mixed> $arguments
 */
function narrowToIntKeys(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		if (!is_int($key)) {
			throw new \Exception('Key must be an int');
		}
	}

	assertType('array<int, mixed>', $arguments);
}

/**
 * @param array<int|string, mixed> $arguments
 */
function narrowWithReturn(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		if (!is_string($key)) {
			return;
		}
	}

	assertType('array<string, mixed>', $arguments);
}

/**
 * @param array<int|string, mixed> $arguments
 */
function continueDoesNotNarrow(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		if (!is_string($key)) {
			continue;
		}
	}

	assertType('array<int|string, mixed>', $arguments);
}

/**
 * @param array<int|string, mixed> $arguments
 */
function breakPreventsNarrowing(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		if (!is_string($key)) {
			throw new \Exception();
		}
		if (rand(0, 1)) {
			break;
		}
	}

	assertType('array<int|string, mixed>', $arguments);
}

/**
 * @param array<int|string, string|null> $arguments
 */
function keyAndValueNarrowing(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		if (!is_string($key)) {
			throw new \Exception();
		}
		$arguments[$key] = $argument ?? '';
	}

	assertType('array<string, string>', $arguments);
}

/**
 * @param array<int|string, mixed> $arguments
 */
function noKeyVar(array $arguments): void
{
	foreach ($arguments as $argument) {
		if (!is_string($argument)) {
			throw new \Exception();
		}
	}

	// Even without a key variable, every element is guaranteed to be a string
	// after the loop, so the value type is narrowed.
	assertType('array<int|string, string>', $arguments);
}

/**
 * @param array<int|string, mixed> $arguments
 */
function keyReassignedPreventsNarrowing(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		$key = 'test';
		if (!is_string($key)) {
			throw new \Exception();
		}
	}

	assertType('array<int|string, mixed>', $arguments);
}

/**
 * @param array<int|string, mixed> $arguments
 */
function narrowWithAssert(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		assert(is_string($key));
	}

	assertType('array<string, mixed>', $arguments);
}

/**
 * @param non-empty-array<int|string, mixed> $arguments
 */
function narrowNonEmptyArray(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		if (!is_string($key)) {
			throw new \Exception();
		}
	}

	assertType('non-empty-array<string, mixed>', $arguments);
}

class Foo
{
	/** @var array<int|string, mixed> */
	private array $prop;

	public function narrowPropertyKey(): void
	{
		foreach ($this->prop as $k => $v) {
			if (!is_string($k)) {
				throw new \Exception();
			}
		}

		assertType('array<string, mixed>', $this->prop);
	}
}

/**
 * @param array<int|string, mixed> $arguments
 */
function partialContinueNarrowingDoesNotApply(array $arguments): void
{
	foreach ($arguments as $key => $argument) {
		if (rand(0, 1)) {
			continue;
		}
		if (!is_string($key)) {
			throw new \Exception();
		}
	}

	assertType('array<int|string, mixed>', $arguments);
}
