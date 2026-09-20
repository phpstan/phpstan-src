<?php declare(strict_types = 1);

namespace VariableVariableName;

use Stringable;

class Greeter
{

	public function greet(): void
	{
		echo $$this; // error - $this is not stringable
	}

	public function greetText(): string
	{
		return 'Hello World';
	}

	public function testNames(string $name, Stringable $stringable, int $int, array $array, object $object): void
	{
		echo $$name; // valid
		echo $$stringable; // valid
		echo $$int; // valid
		echo $$array; // error
		echo $$object; // error
		echo $$this; // error
	}

}
