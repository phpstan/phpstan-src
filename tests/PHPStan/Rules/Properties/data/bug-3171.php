<?php declare(strict_types = 1);

namespace Bug3171;

class PropertyClass {
	/** @var string[] */
	public $someArray;
}

class HelloWorld
{
	/** @var PropertyClass|null */
	private $property;

	public function testPropCoalesce()
	{
		return $this->property->someArray ?? 'test';
	}

	public function testPropTypoCoalesce()
	{
		return $this->property->someArrrrray ?? 'test';
	}

	public function testPropIsset(): bool
	{
		return isset($this->property->someArray);
	}

	public function testPropTypoIsset(): bool
	{
		return isset($this->property->someArrrrray);
	}

	public function testPropOffsetCoalesce(): string
	{
		return $this->property->someArray['test'] ?? 'test';
	}

	public function testPropOffsetTypoCoalesce(): string
	{
		return $this->property->someArrrrray['test'] ?? 'test';
	}

	public function testPropOffsetIsset(): bool
	{
		return isset($this->property->someArray['test']);
	}

	public function testPropOffsetTypoIsset(): bool
	{
		return isset($this->property->someArrrrray['test']);
	}
}
