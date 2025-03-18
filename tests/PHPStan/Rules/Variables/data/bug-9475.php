<?php declare(strict_types = 1);

namespace Bug9475;

use Stringable;

final class Variables
{

	public function test(string $name, Stringable $stringable, object $object, array $array): void
	{
		echo 'Hello, ' . $$this;
		echo 'Hello, ' . ${$this};
		echo 'Hello, ' . ${$object};
		echo 'Hello, ' . ${$array};

		echo 'Hello, ' . ${$name}; // valid
		echo 'Hello, ' . ${$stringable}; // valid
	}

}
