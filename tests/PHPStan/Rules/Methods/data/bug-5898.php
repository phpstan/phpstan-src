<?php declare(strict_types = 1);

namespace Bug5898;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route
{

	public function __construct(string $name)
	{

	}

}

class Sit
{

	#[Route(name: 'test')]
	public function doFoo()
	{

	}

}
