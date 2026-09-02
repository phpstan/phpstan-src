<?php declare(strict_types = 1);

namespace ClassNameComparisonUnknownClass;

use function PHPStan\Testing\assertType;

interface Shape
{
}

function unknownClassStillNarrows(Shape $a): void
{
	if ($a::class === \Totally\Unknown\ClassName::class) {
		assertType('ClassNameComparisonUnknownClass\Shape&Totally\Unknown\ClassName', $a);
	}
}

function unknownClassGetClass(Shape $a): void
{
	if (get_class($a) === \Totally\Unknown\ClassName::class) {
		assertType('ClassNameComparisonUnknownClass\Shape&Totally\Unknown\ClassName', $a);
	}
}
