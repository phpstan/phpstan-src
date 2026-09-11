<?php declare(strict_types = 1);

namespace Bug10783;

class HelloWorld
{
	public function sayHello(): bool
	{
		$a = new \ArrayObject([]);
		$a[] = 'test';
		return empty($a->getArrayCopy());
	}
}
