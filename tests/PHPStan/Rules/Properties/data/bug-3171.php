<?php declare(strict_types = 1);

namespace Bug3171;

class PropertyClass {
	/** @var string[] */
	public $someArray;
}

class Foo
{
	/** @var PropertyClass|null */
	private $property;

	public function testOperator(): string
	{
		return $this->property->someArray['test'] ?? 'test';
	}
}

