<?php

namespace LoopVariables;

use function PHPStan\Testing\assertType;

class ForeachFoo
{

	/** @var int[] */
	private $property = [];

	public function doFoo(string $s)
	{
		$foo = null;
		$key = null;
		$val = null;
		$nullableVal = null;

		$this->property = [];

		$integers = [];
		$i = 0;
		$iterableArray = [];
		if (rand(0, 1) === 0) {
			$iterableArray = [1, 2, 3];
		}
		$falseOrObject = false;
		foreach ($iterableArray as $key => $val) {
			assertType('LoopVariables\Foo|LoopVariables\Lorem|null', $foo);
			assertType('1|10|null', $nullableVal);
			assertType('LoopVariables\Foo|false', $falseOrObject);
			assertType('1|2|3', $val);
			assertType('0|1|2', $key);
			assertType('array<string, 1|2>', $this->property);
			assertType('0|1|2', $i);
			$foo = new Foo();
			assertType('LoopVariables\Foo', $foo);

			if ($nullableVal === null) {
				assertType('null', $nullableVal);
				$nullableVal = 1;
			} else {
				$nullableVal *= 10;
				assertType('10|100', $nullableVal);
			}

			if ($falseOrObject === false) {
				$falseOrObject = new Foo();
			}

			$foo && $i++;

			$nullableInt = $val;
			if (rand(0, 1) === 1) {
				$nullableInt = null;
			}

			if (something()) {
				$foo = new Bar();
				break;
			}
			if (something()) {
				$foo = new Baz();
				return;
			}
			if (something()) {
				$foo = new Lorem();
				continue;
			}

			if ($nullableInt === null) {
				continue;
			}

			if (isset($this->property[$s])) {
				continue;
			}

			$this->property[$s] = $val;

			$integers[] = $nullableInt;

			assertType('LoopVariables\Foo', $foo);

			assertType('LoopVariables\Foo', $falseOrObject);

			assertType('1|2|3', $nullableInt);

			assertType('list{0: 1|2|3, 1?: 1|2|3, 2?: 1|2|3}', $integers);

			assertType('non-empty-array<string, 1|2|3>', $this->property);

			assertType('0|1|2|3', $i);
		}

		$emptyForeachKey = null;
		$emptyForeachVal = null;
		foreach ($iterableArray as $emptyForeachKey => $emptyForeachVal) {

		}

		assertType('1|2|3|null', $val);

		assertType('0|1|2|null', $key);

		assertType('3|null', $emptyForeachVal);

		assertType('2|null', $emptyForeachKey);

		assertType('array{}|array{0: 2, 1?: 3}|array{3}|list{0: 1, 1?: 2|3, 2?: 3}', $integers);

		assertType('array<string, 1|2|3>', $this->property);

		assertType('0|1|2|3', $i);

		assertType('LoopVariables\Bar|LoopVariables\Foo|LoopVariables\Lorem|null', $foo);

		assertType('1|10|100|null', $nullableVal);

		assertType('LoopVariables\Foo|false', $falseOrObject);
	}

}

class ForeachFoo2
{

	/** @var int[] */
	private $property = [];

	/**
	 * @param array<0|1|2, 1|2|3> $iterableArray
	 */
	public function doFoo(string $s, array $iterableArray)
	{
		$foo = null;
		$key = null;
		$val = null;
		$nullableVal = null;

		$this->property = [];

		$integers = [];
		$i = 0;
		$falseOrObject = false;
		foreach ($iterableArray as $key => $val) {
			assertType('LoopVariables\Foo|LoopVariables\Lorem|null', $foo);
			assertType('int<1, max>|null', $nullableVal);
			assertType('LoopVariables\Foo|false', $falseOrObject);
			assertType('1|2|3', $val);
			assertType('0|1|2', $key);
			assertType('array<string, 1|2|3>', $this->property);
			assertType('int<0, max>', $i);
			$foo = new Foo();
			assertType('LoopVariables\Foo', $foo);

			if ($nullableVal === null) {
				assertType('null', $nullableVal);
				$nullableVal = 1;
			} else {
				$nullableVal *= 10;
				assertType('int<10, max>', $nullableVal);
			}

			if ($falseOrObject === false) {
				$falseOrObject = new Foo();
			}

			$foo && $i++;

			$nullableInt = $val;
			if (rand(0, 1) === 1) {
				$nullableInt = null;
			}

			if (something()) {
				$foo = new Bar();
				break;
			}
			if (something()) {
				$foo = new Baz();
				return;
			}
			if (something()) {
				$foo = new Lorem();
				continue;
			}

			if ($nullableInt === null) {
				continue;
			}

			if (isset($this->property[$s])) {
				continue;
			}

			$this->property[$s] = $val;

			$integers[] = $nullableInt;

			assertType('LoopVariables\Foo', $foo);

			assertType('LoopVariables\Foo', $falseOrObject);

			assertType('1|2|3', $nullableInt);

			assertType('non-empty-list<1|2|3>', $integers);

			assertType('non-empty-array<string, 1|2|3>', $this->property);

			assertType('int<0, max>', $i);
		}

		$emptyForeachKey = null;
		$emptyForeachVal = null;
		foreach ($iterableArray as $emptyForeachKey => $emptyForeachVal) {

		}

		assertType('1|2|3|null', $val);

		assertType('0|1|2|null', $key);

		assertType('1|2|3|null', $emptyForeachVal);

		assertType('0|1|2|null', $emptyForeachKey);

		assertType('list<1|2|3>', $integers);

		assertType('array<string, 1|2|3>', $this->property);

		assertType('int<0, max>', $i);

		assertType('LoopVariables\Bar|LoopVariables\Foo|LoopVariables\Lorem|null', $foo);

		assertType('1|int<10, max>|null', $nullableVal);

		assertType('LoopVariables\Foo|false', $falseOrObject);
	}

}
