<?php // lint >= 8.0

namespace ClassAttributesArgumentsByName;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Route
{

	/**
	 * @optional-arguments-by-name
	 */
	public function __construct(string $path, string $name = '', array $methods = [])
	{
	}

}

#[Route('/foo', name: 'foo')]
class Foo
{

}

#[Route('/bar', 'bar')]
class Bar
{

}
