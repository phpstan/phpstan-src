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

class SomeClass5
{
	use MyTrait3;

	public static string $bar = 'bar';
}

class SomeClass6
{
	use MyTrait3;

	public static int $bar = 1;
}

trait MyTrait3
{
	public function getRandom(): int
	{
		$value = random_int(1, 100);
		if (\is_int(self::$bar)) {
			return $value * $value;
		}
		if (\is_int(static::$bar)) {
			return $value * $value;
		}
		if (\is_int($this::$bar)) {
			return $value * $value;
		}

		return $value;
	}
}

class SomeClass7
{
	use MyTrait4;

	public ?string $baz = 'baz';
}

class SomeClass8
{
	use MyTrait4;

	public ?int $baz = 1;
}

trait MyTrait4
{
	public function getRandom(): int
	{
		$value = random_int(1, 100);
		if (\is_int($this?->baz)) {
			return $value * $value;
		}

		return $value;
	}
}
