<?php declare(strict_types=1); // lint >= 8.0

namespace Bug7165;

#[\Attribute]
class MyAttribute
{
	public function __construct(string $name)
	{
	}
}

