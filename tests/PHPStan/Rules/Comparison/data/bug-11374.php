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
