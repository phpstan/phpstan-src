<?php declare(strict_types = 1);

namespace Bug13023;

class SomeClass
{
	use MyTrait;
}

class SomeClass2
{
	use MyTrait;
}

trait MyTrait
{
	public function getRandom(): int
	{
		$value = random_int(1, 100);
		if (is_a($this, SomeClass::class)) {
			return $value * $value;
		}
		return $value;
	}
}

class SomeClass3
{
	use MyTrait2;

	public string $foo = 'foo';
}

class SomeClass4
{
	use MyTrait2;

	public int $foo = 1;
}

trait MyTrait2
{
	public function getRandom(): int
	{
		$value = random_int(1, 100);
		if (\is_int($this->foo)) {
			return $value * $value;
		}

		return $value;
	}
}
