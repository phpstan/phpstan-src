<?php

namespace Bug3405;

class Foo
{

	public function doFoo(
		string $file = __FILE__,
		int $line = __LINE__,
		string $class = __CLASS__,
		string $dir = __DIR__,
		string $namespace = __NAMESPACE__,
		string $method = __METHOD__,
		string $function = __FUNCTION__,
		string $trait = __TRAIT__
	): void
	{
	}

}
