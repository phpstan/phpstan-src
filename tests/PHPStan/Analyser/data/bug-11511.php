<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug11511;

$myObject = new class (new class { public string $bar = 'test'; }) {
	public function __construct(public object $foo)
	{
	}
};
echo $myObject->foo->bar;
