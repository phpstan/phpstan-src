<?php declare(strict_types=1);

namespace ArrayUnionKeepSeparate;

use function PHPStan\Testing\assertType;

class KeepSeparate
{

	/** @param array<int>|array<string> $arr */
	public function plainArray(array $arr): void
	{
		assertType('array<int>|array<string>', $arr);
	}

	/** @param list<int>|list<string> $list */
	public function listUnion(array $list): void
	{
		assertType('list<int>|list<string>', $list);
	}

	/** @param non-empty-array<int, int>|non-empty-array<string, string> $arr */
	public function distinctKeyAndValue(array $arr): void
	{
		assertType('non-empty-array<int, int>|non-empty-array<string, string>', $arr);
	}

	/**
	 * Subsumption: array<int> is a subtype of array<int|string>, so the
	 * union must collapse to the wider member.
	 *
	 * @param array<int>|array<int|string> $arr
	 */
	public function subsumesWider(array $arr): void
	{
		assertType('array<int|string>', $arr);
	}

	/**
	 * Subsumption across list/array: list<int> is a subtype of array<int>.
	 *
	 * @param list<int>|array<int> $arr
	 */
	public function subsumesListIntoArray(array $arr): void
	{
		assertType('array<int>', $arr);
	}

	/**
	 * Identical members dedupe.
	 *
	 * @param array<int>|array<int> $arr
	 */
	public function identicalMembers(array $arr): void
	{
		assertType('array<int>', $arr);
	}

	/**
	 * Narrowing the value via offset-access propagates back to the array.
	 *
	 * @param list<int>|list<string> $list
	 */
	public function narrowByOffset(array $list): void
	{
		if (count($list) === 0) {
			return;
		}

		if (is_string($list[0])) {
			assertType('non-empty-list<string>&hasOffsetValue(0, string)', $list);
		} else {
			assertType('non-empty-list<int>&hasOffsetValue(0, int)', $list);
		}
	}

	/**
	 * A mixed array is not a subtype of a union of homogeneous arrays.
	 *
	 * @param array<int>|array<string> $arr
	 */
	public function acceptsTaker(array $arr): void
	{
	}

	public function callerWithMixedArray(): void
	{
		/** @var array<int|string> $mixed */
		$mixed = [];
		// phpstan-should-error: passing array<int|string> does not satisfy array<int>|array<string>
		$this->acceptsTaker($mixed);
	}

	/**
	 * Constant array stays separate from a general array (no folding into
	 * array<int|string, ...>).
	 *
	 * @param array{foo: int}|array<string, string> $arr
	 */
	public function constantAndGeneral(array $arr): void
	{
		assertType("array{foo: int}|array<string, string>", $arr);
	}

	/**
	 * Iterating a union preserves the element union (existing UnionType
	 * behavior; regression guard for the keep-separate change).
	 *
	 * @param list<int>|list<string> $list
	 */
	public function iteration(array $list): void
	{
		foreach ($list as $value) {
			assertType('int|string', $value);
		}
	}

}
