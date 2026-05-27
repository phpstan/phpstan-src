<?php declare(strict_types = 1);

namespace Bug3385;

use function PHPStan\Testing\assertType;

class Greeter
{

	public function sayHello(): string
	{
		return 'hello';
	}

	public function isEqualTo(Greeter $otherGreeter): bool
	{
		return $this->sayHello() === $otherGreeter->sayHello();
	}

}

function isGreeterDifferent(?Greeter $greeterA, ?Greeter $greeterB): bool
{
	if ($greeterA === null && $greeterB !== null) {
		return true;
	}

	if ($greeterA !== null && $greeterB === null) {
		return true;
	}

	if ($greeterA === null && $greeterB === null) {
		return false;
	}

	assertType('Bug3385\Greeter', $greeterA);
	assertType('Bug3385\Greeter', $greeterB);

	return $greeterA->isEqualTo($greeterB);
}
