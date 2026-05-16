<?php

namespace Bug13510;

use function PHPStan\Testing\assertType;

final class Foo
{

	/** @param non-empty-list<int> $arr */
	public function test(array $arr): void
	{
		array_unshift($arr, array_pop($arr));
		assertType('non-empty-list<int>', $arr);
	}

	/** @param non-empty-list<int> $arr */
	public function testTwoLines(array $arr): void
	{
		$popped = array_pop($arr);
		array_unshift($arr, $popped);
		assertType('non-empty-list<int>', $arr);
	}
}

class Bar
{
	/** @var array<int> */
	public array $arr = [];

	public function test(): void
	{
		if (count($this->arr) === 0) {
			throw new \Exception();
		}
		assertType('non-empty-array<int>', $this->arr);
		array_unshift($this->arr, array_pop($this->arr));
		assertType('non-empty-array<int>', $this->arr);
	}

	public function testArrayPush(): void
	{
		if (count($this->arr) === 0) {
			throw new \Exception();
		}
		array_push($this->arr, array_pop($this->arr));
		assertType('non-empty-array<int>', $this->arr);
	}

	public function testArrayUnshiftWithArrayShift(): void
	{
		if (count($this->arr) === 0) {
			throw new \Exception();
		}
		array_unshift($this->arr, array_shift($this->arr));
		assertType('non-empty-array<int>', $this->arr);
	}

	public function testArrayPushWithArrayShift(): void
	{
		if (count($this->arr) === 0) {
			throw new \Exception();
		}
		array_push($this->arr, array_shift($this->arr));
		assertType('non-empty-array<int>', $this->arr);
	}

	public function testArraySplice(): void
	{
		if (count($this->arr) === 0) {
			throw new \Exception();
		}
		array_splice($this->arr, 0, 0, [array_pop($this->arr)]);
		assertType('non-empty-array<(int<0, max>|string), int>', $this->arr);
	}
}
