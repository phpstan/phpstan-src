<?php declare(strict_types = 1);

namespace DynamicMethodName;

use Stringable;

class Foo
{

	public function doFoo(): void
	{
	}

	public function test(string $name, Stringable $stringable, int $int, object $object): void
	{
		$this->{$this}(); // error - $this is not a string
		$this->$object(); // error - object is not a string
		$this->$stringable(); // error - method names cannot be Stringable
		$this->$int(); // error - int is not a string

		$this->$name(); // valid
	}

}
