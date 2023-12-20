<?php

namespace CheckTypeFunctionCall;

class Foo
{

	/**
	 * @param int $integer
	 * @param int|string $integerOrString
	 * @param string $string
	 * @param callable $callable
	 * @param array $array
	 * @param array<int> $arrayOfInt
	 */
	public function doFoo(
		int $integer,
		$integerOrString,
		string $string,
		callable $callable,
		array $array,
		array $arrayOfInt
	)
	{
		if (is_int($integer)) { // always true

		}
		if (is_int($integerOrString)) { // fine

		}
		if (is_int($string)) { // always false

		}
		$className = 'Foo';
		if (is_a($className, \Throwable::class, true)) { // should be fine

		}
		if (is_array($callable)) {

		}
		if (is_callable($array)) {

		}
		if (is_callable($arrayOfInt)) {

		}

		assert($integer instanceof \stdClass);
	}

}

class TypeCheckInSwitch
{

	public function doFoo($value)
	{
		switch (true) {
			case is_int($value):
			case is_float($value):
				break;
		}
	}

}

class StringIsNotAlwaysCallable
{

	public function doFoo(string $s)
	{
		if (is_callable($s)) {
			$s();
		}
	}

}

class CheckIsCallable
{

	public function test()
	{
		if (is_callable('date')) {

		}
		if (is_callable('nonexistentFunction')) {

		}
	}

}

class IsNumeric
{

	public function test(string $str, float $float)
	{
		if (is_numeric($str)) {

		}
		if (is_numeric('123')) {

		}
		if (is_numeric('blabla')) {

		}

		$isNumeric = $float;
		$maybeNumeric = $float;
		if (doFoo()) {
			$isNumeric = 123;
			$maybeNumeric = 123;
		} else {
			$maybeNumeric = $str;
		}

		if (is_numeric($isNumeric)) {

		}
		if ($maybeNumeric) {

		}
	}

}

class CheckDefaultArrayKeys
{

	/**
	 * @param string[] $array
	 */
	public function doFoo(array $array)
	{
		foreach ($array as $key => $val) {
			if (is_int($key)) {
				return;
			}
			if (is_string($key)) {
				return;
			}
		}
	}

}

class IsSubclassOfTest
{

	public function doFoo(
		string $string,
		?string $nullableString
	)
	{
		is_subclass_of($string, $nullableString);
		is_subclass_of($nullableString, $string);
		is_subclass_of($nullableString, 'Foo');
	}

}

class DefinedConstant
{

	public function doFoo()
	{
		if (defined('DEFINITELY_DOES_NOT_EXIST')) {

		}
		if (!defined('ANOTHER_DEFINITELY_DOES_NOT_EXIST')) {

		}

		$foo = new Foo();
		if (method_exists($foo, 'test')) {

		}
		if (method_exists($foo, 'doFoo')) {

		}
	}

}

final class FinalClassWithMethodExists
{

	public function doFoo()
	{
		if (method_exists($this, 'doFoo')) {

		}
		if (method_exists($this, 'doBar')) {

		}
	}

}

#[\AllowDynamicProperties]
final class FinalClassWithPropertyExists
{

	/** @var int */
	private $fooProperty;

	public function doFoo()
	{
		if (property_exists($this, 'fooProperty')) {

		}
		if (property_exists($this, 'barProperty')) {

		}
	}

}

class InArray
{

	public function doFoo(
		string $s,
		int $i,
		$mixed
	)
	{
		if (in_array('foo', $mixed, true)) {

		}

		if (in_array($s, ['foo' ,'bar'], true)) {

		}
		if (in_array($i, ['foo', 'bar'], true)) {

		}

		$fooOrBar = 'foo';
		if (rand(0, 1) === 0) {
			$fooOrBar = 'bar';
		}

		if (in_array($fooOrBar, ['baz', 'lorem'], true)) {

		}

		if (in_array($fooOrBar, ['foo', 'bar'], true)) {

		}

		if (in_array('foo', ['foo'], true)) {

		}

		if (in_array('foo', ['foo', 'bar'], true)) {

		}

		$arr = ['foo', 'bar'];
		if (rand(0, 1) === 0) {
			$arr = false;
		}

		if (in_array('foo', $arr, true)) {

		}
	}

	/**
	 * @param string $s
	 * @param string[] $strings
	 */
	public function doBar(
		string $s,
		array $strings
	)
	{
		if (in_array($s, $strings, true)) {

		}
	}

	/**
	 * @param string $s
	 * @param array $mixedArray
	 * @param (string|float)[] $stringsOrFloats
	 */
	public function doBaz(
		string $s,
		array $mixedArray,
		array $stringsOrFloats
	)
	{
		if (in_array($s, $mixedArray, true)) {

		}
		if (in_array('s', $mixedArray, true)) {

		}
		if (in_array($s, $stringsOrFloats, true)) {

		}
		if (in_array('s', $stringsOrFloats, true)) {

		}
	}

	public function checkByCondition(int $x)
	{
		$data = [];
		if ($x === 0) {
			$data[] = 'foo';
		}

		if (in_array('foo', $data, true)) {

		}

		if (in_array('bar', $data, true)) {

		}
	}

	public function checkByConditionWithNonEmpty(int $x)
	{
		$data = ['bar'];
		if ($x === 0) {
			$data[] = 'foo';
		}

		if (in_array('foo', $data, true)) {

		}

		if (in_array('baz', $data, true)) {

		}
	}

	public function checkWithEmpty()
	{
		if (in_array('foo', [], true)) {

		}
	}

}

class ArrayKeyExists
{

	public function doFoo(string $s)
	{
		$a = ['a' => 1];
		if (rand(0, 1) === 1) {
			$a['b'] = 2;
		}

		if (array_key_exists('a', $a)) {

		}
		if (array_key_exists('b', $a)) {

		}
		if (array_key_exists('c', $a)) {

		}
		if (array_key_exists($s, $a)) {

		}

		/** @var array<string, string> $stringKeys */
		$stringKeys = doFoo();
		if (array_key_exists($s, $stringKeys)) {

		}

		$b = ['a' => 1, 'b' => 2, 'c' => 3];
		if (array_key_exists($s, $b)) {

		}

		$appleModels = [
			'iPhone1,1' => 'iPhone',
			'iPhone1,2' => 'iPhone 3G',
			'iPhone2,1' => 'iPhone 3GS',
			'iPhone3,1' => 'iPhone 4',
			'iPhone3,2' => 'iPhone 4',
			'iPhone3,3' => 'iPhone 4',
			'iPhone4,1' => 'iPhone 4S',
			'iPhone5,1' => 'iPhone 5',
			'iPhone5,2' => 'iPhone 5',
			'iPhone5,3' => 'iPhone 5C',
			'iPhone5,4' => 'iPhone 5C',
			'iPhone6,1' => 'iPhone 5S',
			'iPhone6,2' => 'iPhone 5S',
			'iPhone7,1' => 'iPhone 6 Plus',
			'iPhone7,2' => 'iPhone 6',
			'iPhone8,1' => 'iPhone 6S',
			'iPhone8,2' => 'iPhone 6S Plus',
			'iPhone8,4' => 'iPhone SE',
			'iPhone9,1' => 'iPhone 7',
			'iPhone9,2' => 'iPhone 7 Plus',
			'iPhone9,3' => 'iPhone 7',
			'iPhone9,4' => 'iPhone 7 Plus',
			'iPhone10,1' => 'iPhone 8',
			'iPhone10,2' => 'iPhone 8 Plus',
			'iPhone10,3' => 'iPhone X',
			'iPhone10,4' => 'iPhone 8',
			'iPhone10,5' => 'iPhone 8 Plus',
			'iPhone10,6' => 'iPhone X',
			'iPad1,1' => 'iPad',
			'iPad2,1' => 'iPad 2',
			'iPad2,2' => 'iPad 2',
			'iPad2,3' => 'iPad 2',
			'iPad2,4' => 'iPad 2',
			'iPad2,5' => 'iPad Mini',
			'iPad2,6' => 'iPad Mini',
			'iPad2,7' => 'iPad Mini',
			'iPad3,1' => 'iPad 3',
			'iPad3,2' => 'iPad 3',
			'iPad3,3' => 'iPad 3',
			'iPad3,4' => 'iPad 4',
			'iPad3,5' => 'iPad 4',
			'iPad3,6' => 'iPad 4',
			'iPad4,1' => 'iPad Air',
			'iPad4,2' => 'iPad Air',
			'iPad4,3' => 'iPad Air',
			'iPad4,4' => 'iPad Mini 2',
			'iPad4,5' => 'iPad Mini 2',
			'iPad4,6' => 'iPad Mini 2',
			'iPad4,7' => 'iPad Mini 3',
			'iPad4,8' => 'iPad Mini 3',
			'iPad4,9' => 'iPad Mini 3',
			'iPad5,1' => 'iPad Mini 4',
			'iPad5,2' => 'iPad Mini 4',
			'iPad5,3' => 'iPad Air 2',
			'iPad5,4' => 'iPad Air 2',
			'iPad6,3' => 'iPad Pro (9.7 inch)',
			'iPad6,4' => 'iPad Pro (9.7 inch)',
			'iPad6,7' => 'iPad Pro (12.9 inch)',
			'iPad6,8' => 'iPad Pro (12.9 inch)',
			'iPod1,1' => 'iPod Touch (1nd Gen)',
			'iPod2,1' => 'iPod Touch (2nd Gen)',
			'iPod3,1' => 'iPod Touch (3rd Gen)',
			'iPod4,1' => 'iPod Touch (4th Gen)',
			'iPod5,1' => 'iPod Touch (5th Gen)',
			'iPod7,1' => 'iPod Touch (6th Gen)',
		];
		if (array_key_exists($s, $appleModels)) {

		}
	}

}

class PropertyExistsUniversalCrate
{

	private $foo;

	/**
	 * @param \stdClass $std
	 * @param \stdClass|self $stdOrSelf
	 */
	public function doFoo(
		\stdClass $std,
		$stdOrSelf
	)
	{
		if (property_exists($std, 'foo')) {

		}
		/*if (property_exists($stdOrSelf, 'foo')) { // To solve this, we'd need FoundPropertyReflection::isNative() to return TrinaryLogic

		}*/
		if (property_exists($stdOrSelf, 'bar')) {

		}
	}

}

class ObjectCallable
{

	/**
	 * @param object $object
	 * @return int
	 */
	public function isStatic($object): int
	{
		return is_callable([$object, 'yo']) ? 1 : 2;
	}

	/**
	 * @param mixed $object
	 */
	public function isStatic2($object): int
	{
		return is_callable([$object, 'yo']) ? 1 : 2;
	}


	/**
	 * @param mixed $object
	 */
	public function isStatic3($object): int
	{
		if(is_object($object)) {
			return is_callable([$object, 'yo']) ? 1 : 2;
		}

		return 0;
	}

}

class ArrayKeyExistsRepeated
{

	public function doFoo(array $data)
	{
		if (array_key_exists('dealers_dealers_id', $data)) {
			$has = true;
		}

		if (!array_key_exists('dealers_dealers_id', $data)) {
			$has = false;
		}
	}

}

class ArrayKeyExistsWithConstantArray
{

	public function test()
	{
		$array = [];
		for ($i = 0; $i < 2; $i++) {
			if (!array_key_exists('x', $array)) {
				$array['x'] = 1;
			}
		}
	}

}

class CheckIsStringOnSubtractedMixed
{

	public function doFoo($mixed)
	{
		if (is_string($mixed)) {
			return;
		}

		if (is_string($mixed)) {
			return;
		}
	}

	public function doBar($mixed)
	{
		if (is_callable($mixed)) {
			return;
		}

		if (is_callable($mixed)) {

		}
	}

}

class MethodExists
{
	public function testWithStringFirstArgument(): void
	{
		/** @var string $string */
		$string = doFoo();

		if (method_exists(MethodExists::class, 'testWithStringFirstArgument')) {
		}

		if (method_exists(MethodExists::class, 'undefinedMethod')) {
		}

		if (method_exists(MethodExists::class, $string)) {
		}

		if (method_exists('UndefinedClass', $string)) {
		}

		if (method_exists('UndefinedClass', 'test')) {
		}

		if (method_exists($string, 'test')) {
		}
	}

	public function testWithNewObjectInFirstArgument(): void
	{
		/** @var string $string */
		$string = doFoo();

		if (method_exists((new MethodExists()), 'testWithNewObjectInFirstArgument')) {
		}

		if (method_exists((new MethodExists()), 'undefinedMethod')) {
		}

		if (method_exists((new MethodExists()), $string)) {
		}
	}
}

trait MethodExistsTrait
{
	public function test()
	{
		if (method_exists($this, 'method')) {
		}

		if (method_exists($this, 'someAnother')) {
		}

		if (method_exists($this, 'unknown')) {
		}

		if (method_exists(get_called_class(), 'method')) {
		}

		if (method_exists(get_called_class(), 'someAnother')) {
		}

		if (method_exists(get_called_class(), 'unknown')) {
		}

		if (method_exists(static::class, 'method')) {
		}

		if (method_exists(static::class, 'someAnother')) {
		}

		if (method_exists(static::class, 'unknown')) {
		}
	}

	public function method()
	{
	}
}

final class MethodExistsWithTrait
{
	use MethodExistsTrait;

	public function someAnother()
	{
		$this->test();
	}
}

class CheckIsStringInElseIf
{

	/**
	 * @param Foo|string $a
	 */
	public function doFoo($a): bool
	{
		if ($a instanceof Foo) {
			return true;
		} elseif (!is_string($a)) {
			throw new \Exception('Not Bar or string');
		}

		return false;
	}

}

class AssertIsNumeric
{

	public function doFoo(string $str, float $float)
	{
		assert(is_numeric($str));
		assert(is_numeric('123'));
		assert(is_numeric('blabla'));

		$isNumeric = $float;
		if (doFoo()) {
			$isNumeric = 123;
		}

		assert(is_numeric($isNumeric));
	}

	/**
	 * @param int|string $item
	 */
	public function doBar($item): void
	{
		if (!is_numeric($item)) {
			throw new \Exception;
		}

		echo $item;
	}

	/**
	 * @param string|float|int $value
	 */
	public function doBaz($value): void
	{
		if (is_numeric($value)) {

		}
	}

	public  function doLorem(string $rating): ?int
	{
		$ratingMapping = [
			'UR'    => 0,
			'NR'    => 0,
			'G'     => 0,
			'PG'    => 8,
			'PG-13' => 13,
			'R'     => 15,
			'NC-17' => 17,
		];

		if (array_key_exists($rating, $ratingMapping)) {
			$rating = $ratingMapping[$rating];
		}

		if (is_numeric($rating)) {

		}
	}

	/**
	 * @param mixed[] $data
	 */
	function doIpsum(array $data): void
	{
		foreach ($data as $index => $element) {
			if (is_numeric($index)) {
				echo "numeric key\n";
			}
		}
	}

}

#[\AllowDynamicProperties]
class Bug2221
{

	public $foo;

	public function doFoo(): void
	{
		if (property_exists(Bug2221::class, 'foo')) {

		}

		assert(property_exists(Bug2221::class, 'foo'));

		if (property_exists(Bug2221::class, 'bar')) {

		}

		assert(property_exists(Bug2221::class, 'bar'));
	}

	public function doBar(self $self): void
	{
		if (property_exists($self, 'foo')) {

		}

		assert(property_exists($self, 'foo'));

		if (property_exists($self, 'bar')) {

		}

		assert(property_exists($self, 'bar'));
	}

	public function doBaz(\stdClass $std): void
	{
		if (property_exists($std, 'foo')) {

		}

		assert(property_exists($std, 'foo'));
	}

	public function doLorem(\SimpleXMLElement $xml): void
	{
		if (property_exists($xml, 'foo')) {

		}

		assert(property_exists($xml, 'foo'));
	}

}

class InArray2
{

	/**
	 * @param array<int> $ints
	 */
	public function doFoo($ints): void
	{
		if ($ints === []) {
			return;
		}

		if (in_array(0, $ints, true)) {

		}
	}

	/**
	 * @param \stdClass $std
	 * @param array<int, \stdClass|null> $stdClassesOrNull
	 */
	public function doBar($std, $stdClassesOrNull): void
	{
		if ($stdClassesOrNull === []) {
			return;
		}

		if (in_array($std, $stdClassesOrNull, true)) {

		}
	}

}

class ArraySearch
{

	/**
	 * @param int $i
	 * @param non-empty-array<int> $is
	 * @return void
	 */
	public function doFoo(int $i, array $is): void
	{
		$res = array_search($i, $is, true);
	}

}

/**
 * @phpstan-assert-if-true int $value
 */
function testIsInt(mixed $value): bool
{
	return is_int($value);
}

function (int $int) {
	if (testIsInt($int)) {

	}
};

class ConditionalAlwaysTrue
{
	public function sayHello(?int $date): void
	{
		if ($date === null) {
		} elseif (is_int($date)) { // always-true should not be reported because last condition
		}

		if ($date === null) {
		} elseif (is_int($date)) { // always-true should be reported, because another condition below
		} elseif (rand(0,1)) {
		}
	}
}

class InArray3
{
    /**
     * @param non-empty-array<int> $nonEmptyInts
     * @param array<string> $strings
     */
    public function doFoo(int $i, string $s, array $nonEmptyInts, array $strings): void
    {
        if (in_array($i, $strings)) {
        }

        if (in_array($i, $strings, false)) {
        }

        if (in_array(5, $strings)) {
        }

        if (in_array(5, $strings, false)) {
        }

        if (in_array($s, $nonEmptyInts)) {
        }

        if (in_array($s, $nonEmptyInts, false)) {
        }

        if (in_array('5', $nonEmptyInts)) {
        }

        if (in_array('5', $nonEmptyInts, false)) {
        }

		if (in_array(1, $strings, true)) {
		}
    }
}

function checkSuperGlobals(): void
{
	foreach ($GLOBALS as $k => $v) {
		if (is_int($k)) {}
	}

	foreach ($_SERVER as $k => $v) {
		if (is_int($k)) {}
	}

	foreach ($_GET as $k => $v) {
		if (is_int($k)) {}
	}

	foreach ($_POST as $k => $v) {
		if (is_int($k)) {}
	}

	foreach ($_FILES as $k => $v) {
		if (is_int($k)) {}
	}

	foreach ($_COOKIE as $k => $v) {
		if (is_int($k)) {}
	}

	foreach ($_SESSION as $k => $v) {
		if (is_int($k)) {}
	}

	foreach ($_REQUEST as $k => $v) {
		if (is_int($k)) {}
	}

	foreach ($_ENV as $k => $v) {
		if (is_int($k)) {}
	}
}
