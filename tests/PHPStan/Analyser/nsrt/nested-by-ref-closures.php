<?php

namespace NestedByRefClosures;

use function PHPStan\Testing\assertType;

class Runner
{

	/** @param callable(): void $callback */
	public function run(callable $callback): void
	{
	}

	public function nested(): void
	{
		$counter = 0;
		$collected = [];
		$this->run(function () use (&$counter, &$collected): void {
			assertType('int<0, max>', $counter);
			assertType("array<'first'|'second'|'third', true>", $collected);
			$counter++;
			$collected['first'] = true;
			$this->run(function () use (&$counter, &$collected): void {
				assertType('int<1, max>', $counter);
				assertType("non-empty-array<'first'|'second'|'third', true>&hasOffsetValue('first', true)", $collected);
				$counter++;
				$collected['second'] = true;
				$this->run(function () use (&$counter, &$collected): void {
					assertType('int<2, max>', $counter);
					assertType("non-empty-array<'first'|'second'|'third', true>&hasOffsetValue('first', true)&hasOffsetValue('second', true)", $collected);
					$counter++;
					$collected['third'] = true;
				});
				assertType('int<2, max>', $counter);
				assertType("non-empty-array<'first'|'second'|'third', true>&hasOffsetValue('first', true)&hasOffsetValue('second', true)", $collected);
			});
			assertType('int<1, max>', $counter);
			assertType("non-empty-array<'first'|'second'|'third', true>&hasOffsetValue('first', true)", $collected);
		});
		assertType('int<0, max>', $counter);
		assertType("array<'first'|'second'|'third', true>", $collected);
	}

	/** @param list<string> $items */
	public function nestedInLoop(array $items): void
	{
		$seen = [];
		foreach ($items as $item) {
			$this->run(function () use (&$seen, $item): void {
				$seen[] = $item;
				$this->run(function () use (&$seen): void {
					$seen[] = 'inner';
				});
			});
		}
		assertType('list<string>', $seen);
	}

	public function immediatelyInvoked(): void
	{
		$value = null;
		(function () use (&$value): void {
			$this->run(function () use (&$value): void {
				$value = new Runner();
			});
		})();
		assertType('NestedByRefClosures\Runner|null', $value);
	}

}
