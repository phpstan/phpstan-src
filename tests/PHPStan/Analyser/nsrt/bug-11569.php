<?php declare(strict_types = 1);

namespace Bug11569;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @return array{name: string, age: int} */
	public function getFoo(): array
	{
		return ['name' => 'John Doe', 'age' => 30];
	}

	public function testKsort(): void
	{
		$value = ['name' => 'John Doe', 'age' => 30];
		ksort($value);
		assertType("array{age: 30, name: 'John Doe'}", $value);
	}

	public function testKrsort(): void
	{
		$value = ['name' => 'John Doe', 'age' => 30];
		krsort($value);
		assertType("array{name: 'John Doe', age: 30}", $value);
	}

	public function testKsortWithArrayValues(): void
	{
		$data = $this->getFoo();
		ksort($data);
		assertType('array{age: int, name: string}', $data);
		$values = array_values($data);
		assertType('array{int, string}', $values);
	}

	public function testKsortWithArrayCombine(): void
	{
		$data = $this->getFoo();
		ksort($data);
		$values = array_values($data);
		$result = array_combine(['age', 'name'], $values);
		assertType('array{age: int, name: string}', $result);
	}

	public function testKsortIntegerKeys(): void
	{
		$list = ['A', 'C', 'B'];
		ksort($list);
		assertType("array{'A', 'C', 'B'}", $list);
	}

	public function testKrsortIntegerKeys(): void
	{
		$list = ['A', 'C', 'B'];
		krsort($list);
		assertType("array{2: 'B', 1: 'C', 0: 'A'}", $list);
	}

	public function testKsortStringKeys(): void
	{
		$value = ['c' => 3, 'a' => 1, 'b' => 2];
		ksort($value);
		assertType('array{a: 1, b: 2, c: 3}', $value);
	}

	public function testKrsortStringKeys(): void
	{
		$value = ['c' => 3, 'a' => 1, 'b' => 2];
		krsort($value);
		assertType('array{c: 3, b: 2, a: 1}', $value);
	}
}
