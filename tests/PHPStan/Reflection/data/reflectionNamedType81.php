<?php declare(strict_types = 1); // lint >= 8.0

namespace Reflection\NamedType;

use function PHPStan\Testing\assertType;

final class Foo
{
	private string $bar = 'hello';

	public function baz(int|bool $tiny): float
	{
		return 4.2;
	}
}

function qux(bool $switch): array
{
	return [];
}

assertType(
	'ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null',
	(new \ReflectionProperty(Foo::class, 'bar'))->getType()
);

$reflectionMethod = new \ReflectionMethod(Foo::class, 'baz');

assertType(
	'ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null',
	$reflectionMethod->getParameters()[0]->getType()
);

assertType(
	'ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null',
	$reflectionMethod->getReturnType()
);

$reflectionFunction = new \ReflectionFunction('qux');

assertType(
	'ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null',
	$reflectionFunction->getParameters()[0]->getType()
);

assertType(
	'ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null',
	$reflectionFunction->getReturnType()
);
