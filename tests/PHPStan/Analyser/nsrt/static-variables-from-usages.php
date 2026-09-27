<?php // lint >= 8.0

namespace StaticVariablesFromUsages;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	public function counter(): int
	{
		static $count = 0;
		assertType('int<0, max>', $count);
		$count++;
		assertType('int<1, max>', $count);

		return $count;
	}

	public function singleton(): self
	{
		static $instance = null;
		assertType('StaticVariablesFromUsages\\Foo|null', $instance);
		if ($instance === null) {
			$instance = new self();
		}

		return $instance;
	}

	public function memoize(string $key): int
	{
		static $cache = [];
		assertType('array<string, int<0, max>>', $cache);
		if (isset($cache[$key])) {
			return $cache[$key];
		}

		$cache[$key] = strlen($key);

		return $cache[$key];
	}

	public function withoutDefault(): void
	{
		static $x;
		assertType("'a'|null", $x);
		$x = 'a';
	}

	public function neverWritten(): void
	{
		static $x = 5;
		assertType('5', $x);
		assertNativeType('5', $x);
	}

	public function withVarTag(): void
	{
		/** @var list<string> $x */
		static $x = [];
		assertType('list<string>', $x);
		$x[] = 'a';
	}

	public function writtenInLoop(array $items): void
	{
		static $seen = false;
		assertType('bool', $seen);
		foreach ($items as $item) {
			$seen = true;
		}
	}

	/** @param list<int> $items */
	public function staticInLoop(array $items): void
	{
		foreach ($items as $item) {
			static $last = null;
			assertType('int|null', $last);
			$last = $item;
		}
	}

	public function throwsInBetween(): void
	{
		static $state = 'idle';
		assertType("'idle'|'running'", $state);
		$state = 'running';
		$this->mayThrow();
		$state = 'idle';
	}

	public function writtenInClosure(): void
	{
		static $calls = 0;
		assertType('int<0, max>', $calls);
		$increment = function () use (&$calls): void {
			$calls++;
		};
		$increment();
	}

	public function referencedAway(): void
	{
		static $x = 1;
		assertType('mixed', $x);
		$y = &$x;
		$y = 'a';
	}

	public function generator(): \Generator
	{
		static $x = 1;
		assertType('mixed', $x);
		$x = 2;
		yield 1;
	}

	private function mayThrow(): void
	{
	}

}

function recursion(int $depth): int
{
	static $level = 0;
	assertType('int<0, max>', $level);
	$level++;
	if ($depth > 0) {
		recursion($depth - 1);
	}
	$level--;

	return $level;
}
