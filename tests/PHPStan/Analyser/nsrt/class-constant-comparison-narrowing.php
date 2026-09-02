<?php // lint >= 8.0

namespace ClassConstantComparisonNarrowing;

use function PHPStan\Testing\assertType;

class Foo
{
}

class A
{

	public const TYPE = 'ClassConstantComparisonNarrowing\Foo';

}

class B
{

	public const TYPE = 'Bar';

}

function nonClassConstantIsNotClassNameNarrowing(A|B $obj): void
{
	if ($obj::TYPE === 'ClassConstantComparisonNarrowing\Foo') {
		assertType('ClassConstantComparisonNarrowing\A|ClassConstantComparisonNarrowing\B', $obj);
	} else {
		assertType('ClassConstantComparisonNarrowing\A|ClassConstantComparisonNarrowing\B', $obj);
	}

	if ($obj::TYPE === 'Bar') {
		assertType('ClassConstantComparisonNarrowing\A|ClassConstantComparisonNarrowing\B', $obj);
	}
}

function classConstantStillNarrows(object $obj): void
{
	if ($obj::class === Foo::class) {
		assertType('ClassConstantComparisonNarrowing\Foo', $obj);
	}
}
