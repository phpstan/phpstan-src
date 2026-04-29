<?php declare(strict_types = 1);

namespace Bug14549;

use function PHPStan\Testing\assertType;

class Foo
{
	public function foo(array $task): void
	{
		if (\is_callable($task)) {
			assertType('list{class-string|object, string}&callable(): mixed', $task);
			assertType('class-string|object', $task[0]);
			assertType('string', $task[1]);

			foreach ($task as $key => $value) {
				assertType('object|string', $value);
				assertType('0|1', $key);
			}
		}
	}

	public function testCallableArrayIterableTypes(callable $value): void
	{
		if (is_array($value)) {
			assertType('list{class-string|object, string}&callable(): mixed', $value);

			foreach ($value as $key => $val) {
				assertType('0|1', $key);
				assertType('object|string', $val);
			}
		}
	}

	/** @param array{string, string} $task */
	public function testConstantArrayNarrowing(array $task): void
	{
		if (\is_callable($task)) {
			assertType('list{class-string, string}&callable(): mixed', $task);
			assertType('class-string', $task[0]);
			assertType('string', $task[1]);
		}
	}

	/** @param array<string> $task */
	public function testTypedArrayNarrowing(array $task): void
	{
		if (\is_callable($task)) {
			// When value type is string, intersect with class-string|object gives class-string
			// and intersect with string gives string
			assertType('list{class-string, string}&callable(): mixed', $task);
		}
	}

	/** @param array<string, mixed> $task */
	public function testStringKeyedArrayNarrowing(array $task): void
	{
		if (\is_callable($task)) {
			assertType('*NEVER*', $task);
		}
	}

	/** @param callable-array $value */
	public function testCallableArrayPhpDoc(array $value): void
	{
		assertType('list{class-string|object, string}&callable(): mixed', $value);
		assertType('class-string|object', $value[0]);
		assertType('string', $value[1]);
	}
}
