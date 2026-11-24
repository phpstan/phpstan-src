<?php declare(strict_types = 1);

namespace Bug15810;

use ReflectionIntersectionType;
use ReflectionNamedType;
use function PHPStan\Testing\assertType;

function test(ReflectionIntersectionType $type): void
{
	assertType('array<ReflectionNamedType>', $type->getTypes());

	foreach ($type->getTypes() as $part) {
		assertType('ReflectionNamedType', $part);
		assertType('string', $part->getName());
	}
}
