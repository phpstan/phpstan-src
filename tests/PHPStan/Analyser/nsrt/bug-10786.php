<?php declare(strict_types = 1);

namespace Bug10786;

use function PHPStan\Testing\assertType;

class Value {
	public ?int	$value = null;
}

class HelloWorld
{
	public function sayHello(Value $a, Value $b): int
	{
		if (is_null($a->value) && is_null($b->value)) {
			throw new \Exception();
		}

		assertType('int', $a->value ?? $b->value);

		return $a->value ?? $b->value;
	}
}
