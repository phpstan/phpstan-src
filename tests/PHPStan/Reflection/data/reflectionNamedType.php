<?php declare(strict_types = 1); // lint >= 8.0

namespace Reflection\NamedType;

use function PHPStan\Testing\assertType;

final class Foo
{
	private $bar = 'hello';

	public function baz(bool $tiny): float
	{
		return 4.2;
	}
}

function qux(bool $switch): array
{
	return [];
}

$reflectionMethod = new \ReflectionMethod(Foo::class, 'baz');

assertType(
	'ReflectionNamedType|null',
	$reflectionMethod->getParameters()[0]->getType()
);

assertType(
	'ReflectionNamedType|null',
	$reflectionMethod->getReturnType()
);

$reflectionFunction = new \ReflectionFunction('qux');

assertType(
	'ReflectionNamedType|null',
	$reflectionFunction->getParameters()[0]->getType()
);

assertType(
	'ReflectionNamedType|null',
	$reflectionFunction->getReturnType()
);
