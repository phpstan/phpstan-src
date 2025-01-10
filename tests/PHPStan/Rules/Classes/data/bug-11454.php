<?php declare(strict_types = 1);

namespace Bug11454;

interface ConstructorInterface
{
	public function __construct(string $a);
}

class Foo implements ConstructorInterface {
	public function __construct(string $a)
	{
	}
}
