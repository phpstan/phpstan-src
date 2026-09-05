<?php // lint >= 8.0

declare(strict_types = 1);

namespace ResultProvenance;

use PHPStan\TrinaryLogic;
use function array_pop;
use function count;
use function get_class;
use function gettype;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class ProvBase {}
class ProvChild extends ProvBase {}

class Foo
{

	/** @param list<string> $parts */
	public function narrowsThroughVariable(array $parts): void
	{
		$n = count($parts);
		if ($n === 2) {
			assertType('array{string, string}', $parts);
		} else {
			assertType('list<string>', $parts);
		}
	}

	/** @param list<string> $parts */
	public function argWrittenAfterAssign(array $parts): void
	{
		$n = count($parts);
		$parts[] = 'x';
		if ($n === 2) {
			// $parts changed since the count() - no shape reconstruction
			assertType('non-empty-list<string>', $parts);
		}
	}

	/** @param list<string> $parts */
	public function targetReassignedAfterAssign(array $parts): void
	{
		$n = count($parts);
		$n = $this->someInt();
		if ($n === 2) {
			// $n no longer holds the count() result
			assertType('list<string>', $parts);
		}
	}

	public function argReassignedGettype(string|bool $v): void
	{
		$type = gettype($v);
		$v = 'hello';
		if ($type === 'boolean') {
			// $v was overwritten - must not intersect with bool
			assertType("'hello'", $v);
		}
	}

	/** @param list<string> $parts */
	public function unsetArgAfterAssign(array $parts): void
	{
		$n = count($parts);
		unset($parts);
		if ($n === 2) {
			assertVariableCertainty(TrinaryLogic::createNo(), $parts);
		}
	}

	/** @param list<string> $parts */
	public function poppedArgAfterAssign(array $parts): void
	{
		$n = count($parts);
		array_pop($parts);
		if ($n === 2) {
			// one element was removed since the count() - no 2-tuple
			assertType('list<string>', $parts);
		}
	}

	/**
	 * @param list<string> $a
	 * @param list<string> $b
	 */
	public function mergeOfDifferentCalls(array $a, array $b, bool $flag): void
	{
		if ($flag) {
			$n = count($a);
		} else {
			$n = count($b);
		}
		if ($n === 2) {
			// neither call survives the merge - which one $n came from is unknown
			assertType('list<string>', $a);
			assertType('list<string>', $b);
		}
	}

	/** @param list<string> $a */
	public function mergeOfSameCall(array $a, bool $flag): void
	{
		if ($flag) {
			$n = count($a);
		} else {
			$n = count($a);
		}
		if ($n === 2) {
			// the same defining call on both sides survives the merge
			assertType('array{string, string}', $a);
		}
	}

	/** @param list<string> $parts */
	public function byRefClosureAfterAssign(array $parts): void
	{
		$n = count($parts);
		$fn = function () use (&$parts): void {
			$parts = ['a', 'b', 'c'];
		};
		$fn();
		if ($n === 2) {
			// the by-ref closure may have replaced $parts
			assertType('non-empty-list<string>', $parts);
		}
	}

	public function switchThroughVariable(ProvBase $object): void
	{
		$class = get_class($object);
		switch ($class) {
			case ProvChild::class:
				assertType('ResultProvenance\ProvChild', $object);
				break;
			default:
				assertType('ResultProvenance\ProvBase', $object);
		}
	}

	/** @param list<list<string>> $matrix */
	public function loopReassignsEachIteration(array $matrix): void
	{
		foreach ($matrix as $row) {
			$n = count($row);
			if ($n === 2) {
				assertType('array{string, string}', $row);
			}
			$row = ['x'];
		}
	}

	public function someInt(): int
	{
		return 5;
	}

}
