<?php declare(strict_types = 1);

namespace Bug13510;

class Foo {
	/** @var array<int> */
	public array $arr = [];

	public function testArrayUnshift(): void {
		if (count($this->arr) === 0) {
			throw new \Exception('Narrow to non-empty-array');
		}
		array_unshift($this->arr, array_pop($this->arr));
	}

	public function testArrayPush(): void {
		if (count($this->arr) === 0) {
			throw new \Exception('Narrow to non-empty-array');
		}
		array_push($this->arr, array_pop($this->arr));
	}

	public function testArraySplice(): void {
		if (count($this->arr) === 0) {
			throw new \Exception('Narrow to non-empty-array');
		}
		array_splice($this->arr, 0, 0, [array_pop($this->arr)]);
	}
}
