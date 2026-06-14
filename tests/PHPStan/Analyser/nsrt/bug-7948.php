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
}
