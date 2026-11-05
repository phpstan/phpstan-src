<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11374;

class HelloWorld
{
	public function test(float|int $value): string
    {
        return match (true) {
            is_int($value) => (string) $value,
            is_float($value) => (string) $value,
        };
    }

	public function test2(float|int $value): string
    {
        return match (true) {
            $this->isInt($value) => (string) $value,
            $this->isFloat($value) => (string) $value,
        };
    }

	public function test3(float|int $value): string
    {
        return match (true) {
            is_int($value) => (string) $value,
            $this->isFloat($value) => (string) $value,
        };
    }

	public function test4(float|int $value): string
    {
        return match (true) {
            $this->isInt($value) => (string) $value,
            is_float($value) => (string) $value,
        };
    }

	/**
	 * @phpstan-assert-if-true float $value
	 */
	private function isFloat(mixed $value): bool
	{
		return is_float($value);
	}

	/**
	 * @phpstan-assert-if-true int $value
	 */
	private function isInt(mixed $value): bool
	{
		return is_int($value);
	}
}

class A {}
class B {}

final class Analogous
{

	private int|float $prop = 1;

	public function setProp(int|float $prop): void
	{
		$this->prop = $prop;
	}

	public function staticCall(float|int $value): string
	{
		return match (true) {
			self::isIntStatic($value) => (string) $value,
			self::isFloatStatic($value) => (string) $value,
		};
	}

	public function functionCall(float|int $value): string
	{
		return match (true) {
			isIntFunction($value) => (string) $value,
			isFloatFunction($value) => (string) $value,
		};
	}

	public function propertyFetch(): string
	{
		return match (true) {
			self::isIntStatic($this->prop) => (string) $this->prop,
			self::isFloatStatic($this->prop) => (string) $this->prop,
		};
	}

	/**
	 * @param array{x: int|float} $a
	 */
	public function arrayOffset(array $a): string
	{
		return match (true) {
			self::isIntStatic($a['x']) => 'int',
			self::isFloatStatic($a['x']) => 'float',
		};
	}

	public function multipleConditions(float|int|string $value): string
	{
		return match (true) {
			self::isIntStatic($value), is_string($value) => 'int or string',
			self::isFloatStatic($value) => 'float',
		};
	}

	public function instanceofUnion(A|B $obj): string
	{
		return match (true) {
			$obj instanceof A => 'a',
			$obj instanceof B => 'b',
		};
	}

	public function notExhaustiveObject(object $obj): string
	{
		return match (true) {
			$obj instanceof A => 'a',
			$obj instanceof B => 'b',
		};
	}

	public function notExhaustiveAssert(float|int|string $value): string
	{
		return match (true) {
			self::isIntStatic($value) => 'int',
			self::isFloatStatic($value) => 'float',
		};
	}

	/**
	 * @phpstan-assert-if-true float $value
	 */
	private static function isFloatStatic(mixed $value): bool
	{
		return is_float($value);
	}

	/**
	 * @phpstan-assert-if-true int $value
	 */
	private static function isIntStatic(mixed $value): bool
	{
		return is_int($value);
	}

}

/**
 * @phpstan-assert-if-true float $value
 */
function isFloatFunction(mixed $value): bool
{
	return is_float($value);
}

/**
 * @phpstan-assert-if-true int $value
 */
function isIntFunction(mixed $value): bool
{
	return is_int($value);
}

final class MoreAnalogous
{

	private int|float $prop = 1;

	public function matchFalse(float|int $value): string
	{
		return match (false) {
			self::isNotInt($value) => 'int',
			self::isNotFloat($value) => 'float',
		};
	}

	public function assertOnProperty(): string
	{
		return match (true) {
			$this->propIsInt() => 'int',
			$this->propIsFloat() => 'float',
		};
	}

	public function notExhaustiveMatchFalse(float|int|string $value): string
	{
		return match (false) {
			self::isNotInt($value) => 'int',
			self::isNotFloat($value) => 'float',
		};
	}

	/**
	 * @phpstan-assert-if-false int $value
	 */
	private static function isNotInt(mixed $value): bool
	{
		return !is_int($value);
	}

	/**
	 * @phpstan-assert-if-false float $value
	 */
	private static function isNotFloat(mixed $value): bool
	{
		return !is_float($value);
	}

	/**
	 * @phpstan-assert-if-true int $this->prop
	 */
	private function propIsInt(): bool
	{
		return is_int($this->prop);
	}

	/**
	 * @phpstan-assert-if-true float $this->prop
	 */
	private function propIsFloat(): bool
	{
		return is_float($this->prop);
	}

}
