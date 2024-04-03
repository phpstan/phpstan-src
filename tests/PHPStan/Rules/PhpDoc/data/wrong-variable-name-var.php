<?php

namespace WrongVariableNameVarTag;

class Foo
{

	public function doFoo()
	{
		/** @var int $test */
		$test = doFoo();

		/** @var int */
		$test = doFoo();

		/** @var int $foo */
		$test = doFoo();

		/**
		 * @var int
		 * @var string
		 */
		$test = doFoo();
	}

	public function doBar(array $list)
	{
		/** @var int[] $list */
		foreach ($list as $key => $var) {

		}

		/** @var int $key */
		foreach ($list as $key => $var) {

		}

		/** @var int $var */
		foreach ($list as $key => $var) {

		}

		/**
		 * @var int $foo
		 * @var int $bar
		 * @var int $baz
		 * @var int $lorem
		 */
		foreach ($list as $key => [$foo, $bar, [$baz, $lorem]]) {

		}

		/**
		 * @var int $foo
		 * @var int $bar
		 * @var int $baz
		 * @var int $lorem
		 */
		foreach ($list as $key => list($foo, $bar, list($baz, $lorem))) {

		}

		/**
		 * @var int $foo
		 */
		foreach ($list as $key => $val) {

		}

		/** @var int */
		foreach ($list as $key => $val) {

		}
	}

	public function doBaz()
	{
		/** @var int $var */
		static $var;

		/** @var int */
		static $var2;

		/** @var int */
		static $var3, $bar;

		/**
		 * @var int
		 * @var string
		 */
		static $var4, $bar2;

		/** @var int $foo */
		static $test;
	}

	public function doLorem($test)
	{
		/** @var int $test */
		$test2 = doFoo();

		/** @var int */
		$test->foo();

		/** @var int $test */
		$test->foo();

		/** @var int $foo */
		$test->foo();
	}

	public function multiplePrefixedTagsAreFine()
	{
		/**
		 * @var int
		 * @phpstan-var int
		 * @psalm-var int
		 * @phan-var int
		 */
		$test = doFoo(); // OK

		/**
		 * @var int
		 * @var string
		 */
		$test = doFoo(); // error
	}

	public function testEcho($a)
	{
		/** @var string $a */
		echo $a;

		/** @var string $b */
		echo $a;
	}

	public function throwVar($a)
	{
		/** @var \Exception $a */
		throw $a;
	}

	public function throwVar2($a)
	{
		/** @var \Exception */
		throw $a;
	}

	public function throwVar3($a)
	{
		/**
		 * @var \Exception
		 * @var \InvalidArgumentException
		 */
		throw $a;
	}

	public function returnVar($a)
	{
		/** @var \stdClass $a */
		return $a;
	}

	public function returnVar2($a)
	{
		/** @var \stdClass */
		return $a;
	}

	public function returnVar3($a)
	{
		/**
		 * @var \stdClass
		 * @var \DateTime
		 */
		return $a;
	}

	public function thisInVar1()
	{
		/** @var Repository $this */
		$this->demo();
	}

	public function thisInVar2()
	{
		/** @var Repository $this */
		$demo = $this->demo();
	}

	public function overrideDifferentVariableAboveAssign()
	{
		$foo = 'foo';

		/** @var int $foo */
		$bar = $foo + 1;
	}

	public function testIf($foo)
	{
		/** @var int $foo */
		do {

		} while (true);
	}

	public function testIf2()
	{
		/** @var int $foo */
		do {

		} while (true);
	}

}

class Bar
{

	/** @var int */
	private $test;

	/** @var string */
	const TEST = 'str';

}

class ForeachJustValueVar
{

	public function doBar(array $list)
	{
		/** @var int */
		foreach ($list as $val) {

		}
	}

}

class MultipleDocComments
{

	public function doFoo(): void
	{
		/** @var int $foo */
		/** @var string $bar */
		echo 'foo';
	}

	public function doBar(array $slots): void
	{
		/** @var \stdClass[] $itemSlots */
		/** @var \stdClass[] $slots */
		$itemSlots = [];
	}

	public function doBaz(): void
	{
		/** @var \stdClass[] $itemSlots */
		/** @var \stdClass[] $slots */
		$itemSlots = [];
	}

	public function doLorem(): void
	{
		/** @var \stdClass[] $slots */
		$itemSlots['foo'] = 'bar';
	}

	public function doIpsum(): void
	{
		/** @var \stdClass[] */
		$itemSlots['foo'] = 'bar';
	}

	public function doDolor(): void
	{
		/** @var \stdClass[] $slots */
		$itemSlots = [];

		/** @var int $test */
		[[$test]] = doFoo();
	}

	public function doSit(): void
	{
		/**
		 * @var int $foo
		 * @var int $bar
		 */
		[$foo, $bar] = doFoo();
	}

}

/**
 * @var string
 */
class VarInWrongPlaces
{

	/** @var int $a */
	public function doFoo($a)
	{

	}

}

/** @var int */
function doFoo(): void
{

}
