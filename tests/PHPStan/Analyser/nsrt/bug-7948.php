<?php declare(strict_types = 1);

namespace Bug7948;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param string|array<string, mixed> $name
	 * @param mixed $value
	 */
	public function testMixed($name, $value): void
	{
		if (is_array($name)) {
			$value = null;
		}

		if (is_array($name)) {
			assertType('null', $value);
		}
	}

	/**
	 * @param string|array<string, mixed> $name
	 * @param int $value
	 */
	public function testInt($name, $value): void
	{
		if (is_array($name)) {
			$value = null;
		}

		if (is_array($name)) {
			assertType('null', $value);
		}
	}

	/**
	 * Assigned value (5) is a subtype of the original type (int|string),
	 * so the merged type absorbs it just like null|mixed does.
	 *
	 * @param string|array<string, mixed> $name
	 * @param int|string $value
	 */
	public function testSubtype($name, $value): void
	{
		if (is_array($name)) {
			$value = 5;
		}

		if (is_array($name)) {
			assertType('5', $value);
		}
	}

	/**
	 * @param string|array<string, mixed> $name
	 * @param mixed $value
	 */
	public function testMixedNegated($name, $value): void
	{
		if (!is_array($name)) {
			$value = null;
		}

		if (!is_array($name)) {
			assertType('null', $value);
		}
	}

	/**
	 * Boolean-flag guard variant: assigning null to a ?string absorbs into
	 * string|null, so $cwd must re-narrow to null under the repeated flag check.
	 */
	public function testBooleanFlag(?string $cwd, bool $initialClone = false): void
	{
		if ($initialClone) {
			$cwd = null;
		}

		if ($initialClone) {
			assertType('null', $cwd);
		}
	}
}
