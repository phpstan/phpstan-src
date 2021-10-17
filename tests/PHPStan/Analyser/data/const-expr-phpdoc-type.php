<?php

namespace ConstExprPhpDocType;

use RecursiveIteratorIterator as Rec;
use function PHPStan\Testing\assertType;

class Foo
{

	public const SOME_CONSTANT       = 1;
	public const SOME_OTHER_CONSTANT = 2;
	public const YET_ONE_CONST = 3;
	public const YET_ANOTHER_CONST = 4;
	public const DUMMY_SOME_CONSTANT = 99; // should only match the *

	/**
	 * @param 'foo'|'bar' $one
	 * @param self::SOME_* $two
	 * @param self::SOME_OTHER_CONSTANT $three
	 * @param \ConstExprPhpDocType\Foo::SOME_CONSTANT $four
	 * @param Rec::LEAVES_ONLY $five
	 * @param 1.0 $six
	 * @param 234 $seven
	 * @param self::SOME_OTHER_* $eight
	 * @param self::* $nine
	 * @param self::*_CONST $ten
	 * @param self::YET_*_CONST $eleven
	 * @param self::*OTHER* $twelve
	 */
	public function doFoo(
		$one,
		$two,
		$three,
		$four,
		$five,
		$six,
		$seven,
		$eight,
		$nine,
		$ten,
		$eleven,
		$twelve
	)
	{
		assertType("'bar'|'foo'", $one);
		assertType('1|2', $two);
		assertType('2', $three);
		assertType('1', $four);
		assertType('0', $five);
		assertType('1.0', $six);
		assertType('234', $seven);
		assertType('2', $eight);
		assertType('1|2|3|4|99', $nine);
		assertType('3|4', $ten);
		assertType('3|4', $eleven);
		assertType('2|4', $twelve);
	}

}


class HelloWorld
{
	public const NUMBER_TYPE_OFF      = 1;
	public const NUMBER_TYPE_HEAD     = 2;
	public const NUMBER_TYPE_POSITION = 3;

	/**
	 * @var int
	 * @phpstan-var HelloWorld::NUMBER_TYPE_*
	 */
	private $newValue = self::NUMBER_TYPE_OFF;

	/**
	 * @return array<HelloWorld::NUMBER_TYPE_*,string>
	 */
	public static function getArrayConstAsKey()
	{
		return [
			self::NUMBER_TYPE_OFF      => 'Off',
			self::NUMBER_TYPE_HEAD     => 'Head',
			self::NUMBER_TYPE_POSITION => 'Position',
		];
	}

	/**
	 * @return array<int, HelloWorld::NUMBER_TYPE_*>
	 */
	public static function getArrayConstAsValue()
	{
		return [
			self::NUMBER_TYPE_OFF,
			self::NUMBER_TYPE_HEAD,
			self::NUMBER_TYPE_POSITION,
		];
	}

	/**
	 * @return void
	 */
	public function checkConstViaArrayKey()
	{
		$numberArray = self::getArrayConstAsKey();
		assertType("array<1|2|3, string>", $numberArray);

		// ---

		$newvalue = $this->getIntFromPost('newValue');

		if ($newvalue && array_key_exists($newvalue, $numberArray)) {
			assertType("1|2|3", $newvalue);
			$this->newValue = $newvalue;
		}

		// ---

		$newvalue = $this->getIntFromPostWithoutNull('newValue');

		if ($newvalue && array_key_exists($newvalue, $numberArray)) {
			assertType("1|2|3", $newvalue);
			$this->newValue = $newvalue;
		}

		// ---

		$newvalue = 1;

		if ($newvalue && array_key_exists($newvalue, $numberArray)) {
			assertType("1", $newvalue);
			$this->newValue = $newvalue;
		}
	}

	/**
	 * @return void
	 */
	public function checkConstViaArrayValue()
	{
		$numberArray = self::getArrayConstAsValue();
		assertType("array<int, 1|2|3>", $numberArray);

		// ---

		$newvalue = $this->getIntFromPost('newValue');

		if ($newvalue && in_array($newvalue, $numberArray, true)) {
			assertType("1|2|3", $newvalue);
			$this->newValue = $newvalue;
		}

		// ---

		$newvalue = $this->getIntFromPostWithoutNull('newValue');

		if ($newvalue && in_array($newvalue, $numberArray, true)) {
			assertType("1|2|3", $newvalue);
			$this->newValue = $newvalue;
		}

		// ---

		$newvalue = 1;

		if ($newvalue && in_array($newvalue, $numberArray, true)) {
			assertType("1", $newvalue);
			$this->newValue = $newvalue;
		}
	}

	/**
	 * @param string $key
	 * @return int|null
	 */
	public function getIntFromPost($key)
	{
		return isset($_POST[$key]) ? (int)$_POST[$key] : null;
	}

	/**
	 * @param string $key
	 * @return int
	 */
	public function getIntFromPostWithoutNull($key)
	{
		return isset($_POST[$key]) ? (int)$_POST[$key] : 0;
	}
}

class HelloWorld2
{
	/**
	 * @param array{internal?: string[], real?: string[]} $arrays
	 */
	public function sayHello(int $index, string $type, array $arrays): void
	{
		if (!in_array($type, ["real", "internal"], true)) {
			throw new \Exception();
		}

		$bar = array_key_exists($type, $arrays)
		       && array_key_exists($index, $arrays[$type])
		       && $arrays[$type][$index] !== "0"
			? (int)$arrays[$type][$index]
			: null;

		assertType("int|null", $bar);
	}

	/**
	 * @param array{internal?: string[], real?: string[]} $arrays
	 */
	public function sayHello2(int $index, string $type, array $arrays): void
	{
		if (!in_array($type, ["real", "internal"], true)) {
			throw new \Exception();
		}

		if (array_key_exists($type, $arrays)) {
			assertType("'internal'|'real'", $type);
			if (array_key_exists($index, $arrays[$type])) {
				assertType("int", $index);
			}
		}
	}
}
