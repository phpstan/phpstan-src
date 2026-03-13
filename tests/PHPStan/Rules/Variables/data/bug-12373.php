<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug12373;

class HelloWorld
{
	public function sayHello(int $id): void
	{
		$foo = [];

		if ($id)
		{
			$foo = 'foo';
		}
		else
		{
			$value = 'my value';
		}

		$foo = "foo";

		if (!$id)
		{
			echo 'value: ' . $value;
		}
	}
}
