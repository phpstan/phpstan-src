<?php declare(strict_types = 1);

namespace Bug11101PureClosure;

class Foo
{

	/** @param list<string> $args */
	public function test(array $args): void
	{
		$pureFx = static function (string $v): void {};

		assert(isset($args[0]));

		$pureFx($args[0]);

		array_map($pureFx, $args);
	}

}
