<?php declare(strict_types = 1);

namespace Bug10786;

use function PHPStan\Testing\assertType;

class Value {
	public ?int $value = null;
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

	public function sayHello2(Value $a, Value $b): int
	{
		if ($a->value === null && $b->value === null) {
			throw new \Exception();
		}

		assertType('int|null', $a->value);
		assertType('int|null', $b->value);
		assertType('int', $a->value ?? $b->value);

		return $a->value ?? $b->value;
	}
}
