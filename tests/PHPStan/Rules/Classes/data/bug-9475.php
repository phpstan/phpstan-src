<?php // lint >=8.3

namespace Bug9475;

use Stringable;

final class Classes
{

	public function testStaticMethods(string $name, Stringable $stringable, object $object, array $array): void
	{
		echo 'Hello, ' . self::{$this};
		echo 'Hello, ' . self::{$object};
		echo 'Hello, ' . self::{$array};

		echo 'Hello, ' . self::{$name}; // valid
		echo 'Hello, ' . self::{$stringable}; // valid
	}

}
