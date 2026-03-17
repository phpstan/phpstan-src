<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9601;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public string $message = '';
}

class HelloWorld2
{
	public string $message = '';
}

/**
 * @param mixed $object
 */
function test($object): void
{
	$objectName = match (true) {
		$object instanceof HelloWorld => $object::class,
		$object instanceof HelloWorld2 => $object::class,
		default => throw new \LogicException(),
	};

	assertType('Bug9601\HelloWorld|Bug9601\HelloWorld2', $object);
}
