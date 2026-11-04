<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11374Two;

final class HelloWorld
{
	public function functions(float|int $value): string
	{
		return match (true) {
			isInt($value) => (string) $value,
			isFloat($value) => (string) $value,
		};
	}

	public function staticMethods(float|int $value): string
	{
		return match (true) {
			self::isIntStatic($value) => (string) $value,
			self::isFloatStatic($value) => (string) $value,
		};
	}

	public function assertIfFalse(float|int $value): string
	{
		return match (false) {
			$this->isNotInt($value) => (string) $value,
			$this->isNotFloat($value) => (string) $value,
		};
	}

	public function stillUnhandled(float|int|string $value): string
	{
		return match (true) {
			$this->isInt($value) => (string) $value,
			$this->isFloat($value) => (string) $value,
		};
	}

	/** @phpstan-assert-if-true float $value */
	private function isFloat(mixed $value): bool
	{
		return is_float($value);
	}

	/** @phpstan-assert-if-true int $value */
	private function isInt(mixed $value): bool
	{
		return is_int($value);
	}

	/** @phpstan-assert-if-false float $value */
	private function isNotFloat(mixed $value): bool
	{
		return !is_float($value);
	}

	/** @phpstan-assert-if-false int $value */
	private function isNotInt(mixed $value): bool
	{
		return !is_int($value);
	}

	/** @phpstan-assert-if-true float $value */
	private static function isFloatStatic(mixed $value): bool
	{
		return is_float($value);
	}

	/** @phpstan-assert-if-true int $value */
	private static function isIntStatic(mixed $value): bool
	{
		return is_int($value);
	}
}

/** @phpstan-assert-if-true float $value */
function isFloat(mixed $value): bool
{
	return is_float($value);
}

/** @phpstan-assert-if-true int $value */
function isInt(mixed $value): bool
{
	return is_int($value);
}
