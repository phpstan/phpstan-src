<?php declare(strict_types = 1);

namespace IndexedAssignRhsContainerMutation;

use function PHPStan\Testing\assertType;

function takesArray(array $x): int
{
	return count($x);
}

class Holder
{

	/** @var array<int, int> */
	public array $prop = [];

	/** @phpstan-impure */
	public function resetProp(): int
	{
		$this->prop = [];
		return 5;
	}

}

function reassignedInRhs(): array
{
	$arr = ['a' => 1];
	$arr[2] = takesArray($arr = ['b' => 2]);
	assertType('array{b: 2, 2: int}', $arr);

	return $arr;
}

function propertyInvalidatedByImpureRhs(Holder $h): void
{
	$h->prop = [1 => 1];
	$h->prop[2] = $h->resetProp();
	assertType('non-empty-array<int, int>&hasOffsetValue(2, int)', $h->prop);
}
