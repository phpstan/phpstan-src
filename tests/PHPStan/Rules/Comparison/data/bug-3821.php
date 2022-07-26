<?php

namespace Bug3821;

abstract class AbstractMap {}

class HelloWorld
{
	public function fieldExists(AbstractMap $map, string $mapFieldName): bool
	{
		return array_key_exists($mapFieldName, (array) $map);
	}
}
