<?php declare(strict_types = 1);

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
