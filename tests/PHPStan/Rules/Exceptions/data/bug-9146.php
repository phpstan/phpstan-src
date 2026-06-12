<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug9146;

final class HelloWorld
{
	public int $number;
	public function __construct(mixed $number)
	{
		try {
			$this->number = $number;
		} catch (\TypeError $e) {
			throw new \UnexpectedValueException();
		}
	}
}

final class HelloWorld2
{
	public string $name;
	public function setName(mixed $value): void
	{
		try {
			$this->name = $value;
		} catch (\TypeError $e) {
			throw new \InvalidArgumentException('Expected string');
		}
	}
}

final class HelloWorld3
{
	public float $amount;
	public function setAmount(mixed $value): void
	{
		try {
			$this->amount = $value;
		} catch (\TypeError $e) {
			echo "caught";
		}
	}
}

// Dead catch: int assigned to ?float, PHP coerces int to float without TypeError
final class FloatNullCoercion
{
	public ?float $amount;
	public function setAmount(int $value): void
	{
		try {
			$this->amount = $value;
		} catch (\TypeError $e) { // error: Dead catch - TypeError is never thrown in the try block.
			echo "caught";
		}
	}
}

// Not dead: int|string assigned to int, string part could throw TypeError
final class PartialTypeMatch
{
	public int $number;
	/** @param int|string $value */
	public function setNumber($value): void
	{
		try {
			$this->number = $value;
		} catch (\TypeError $e) {
			echo "caught";
		}
	}
}

final class MixedWillNotThrow
{
	public mixed $name;
	public function setName(mixed $value): void
	{
		try {
			$this->name = $value;
		} catch (\TypeError $e) {
			throw new \InvalidArgumentException('Expected string');
		}
	}
}

final class IntDoesNotAcceptFloat
{
	public int $amount;
	public function setAmount(float $value): void
	{
		try {
			$this->amount = $value;
		} catch (\TypeError $e) {
			echo "caught";
		}
	}
}
