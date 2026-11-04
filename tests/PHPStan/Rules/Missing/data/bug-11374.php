<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11374Missing;

final class HelloWorld
{
	public function methods(float|int $value): string
	{
		if ($this->isInt($value)) {
			return 'int';
		} elseif ($this->isFloat($value)) {
			return 'float';
		}
	}

	public function staticMethods(float|int $value): string
	{
		if (self::isIntStatic($value)) {
			return 'int';
		} elseif (self::isFloatStatic($value)) {
			return 'float';
		}
	}

	public function functions(float|int $value): string
	{
		if (isInt($value)) {
			return 'int';
		} elseif (isFloat($value)) {
			return 'float';
		}
	}

	public function stillMissing(float|int|string $value): string
	{
		if ($this->isInt($value)) {
			return 'int';
		} elseif ($this->isFloat($value)) {
			return 'float';
		}
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
