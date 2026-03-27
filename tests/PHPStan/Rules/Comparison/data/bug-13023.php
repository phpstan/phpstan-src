<?php

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
