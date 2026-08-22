<?php

namespace BinaryOperations\NestedNamespace;

use function PHPStan\Testing\assertType;

class Foo
{

	public const INT_CONST = 1;

	public function doFoo(array $generalArray)
	{
		/** @var float $float */
		$float = doFoo();
		$float1 = $float;
		$float2 = $float;
		$float3 = $float;
		$float4 = $float;
		$float5 = $float;
		$float6 = $float;
		$float7 = $float;

		/** @var int $integer */
		$integer = doFoo();
		$integer1 = $integer;
		$integer2 = $integer;
		$integer3 = $integer;
		$integer4 = $integer;
		$integer5 = $integer;
		$integer6 = $integer;
		$integer7 = $integer;
		$integer8 = $integer;

		/** @var bool $bool */
		$bool = doFoo();

		/** @var string $string */
		$string = doFoo();
		$string1 = $string;
		$string2 = $string;
		$string3 = $string;
		$string4 = $string;

		$fooString = 'foo';
		$fooString1 = $fooString;
		$fooString2 = $fooString;
		$fooString3 = $fooString;
		$fooString4 = $fooString;

		/** @var string|null $stringOrNull */
		$stringOrNull = doFoo();

		$arrayOfIntegers = [$integer, $integer + 1, $integer + 2];
		$arrayOfIntegers1 = $arrayOfIntegers;
		$arrayOfIntegers2 = $arrayOfIntegers;
		$arrayOfIntegers3 = $arrayOfIntegers;

		$foo = new Foo();

		$one = 1;
		$one1 = $one;
		$one2 = $one;
		$one3 = $one;
		$one4 = $one;

		$array = [1, 2, 3];

		reset($array);

		/** @var number $number */
		$number = doFoo();

		/** @var int|null|bool $otherInteger */
		$otherInteger = doFoo();

		/** @var mixed $mixed */
		$mixed = doFoo();

		/** @var int[] $arrayOfUnknownIntegers */
		$arrayOfUnknownIntegers = doFoo();

		$foobarString = $fooString;
		$foobarString[6] = 'b';
		$foobarString[7] = 'a';
		$foobarString[8] = 'r';

		$std = new \stdClass();

		/** @var int[] $arrToPush */
		$arrToPush = doFoo();
		array_push($arrToPush, 'foo', new \stdClass());

		/** @var int[] $arrToPush2 */
		$arrToPush2 = doFoo();
		array_push($arrToPush2, ...['foo', new \stdClass()]);

		$arrToUnshift = ['foo' => new \stdClass(), 5 => 'test'];
		array_unshift($arrToUnshift, 'lorem', 5);

		/** @var int[] $arrToUnshift2 */
		$arrToUnshift2 = doFoo();
		array_unshift($arrToUnshift2, 'lorem', new \stdClass());
		array_unshift($mixed, 'lorem');

		$line = __LINE__;
		$dir = __DIR__;
		$file = __FILE__;
		$namespace = __NAMESPACE__;
		$class = __CLASS__;
		$method = __METHOD__;
		$function = __FUNCTION__;

		$incrementedString = $string;
		$incrementedString++;

		$decrementedString = $string;
		$decrementedString--;

		$incrementedFooString = $fooString;
		$incrementedFooString++;

		$decrementedFooString = $fooString;
		$decrementedFooString--;

		$index = 0;
		$preIncArray = [];
		$preIncArray[++$index] = $index;
		$preIncArray[++$index] = $index;

		$anotherIndex = 0;
		$postIncArray = [];
		$postIncArray[$anotherIndex++] = $anotherIndex++;
		$postIncArray[$anotherIndex++] = $anotherIndex++;

		$anotherPostIncArray = [];
		$anotherAnotherIndex = 0;
		$anotherPostIncArray[$anotherAnotherIndex++][$anotherAnotherIndex++][$anotherAnotherIndex++] = $anotherAnotherIndex++;
		$anotherPostIncArray[$anotherAnotherIndex++][$anotherAnotherIndex++][$anotherAnotherIndex++] = $anotherAnotherIndex++;

		$conditionalArray = [1, 1, 1];
		$conditionalInt = 1;
		$conditionalString = 'foo';
		$anotherConditionalString = 'lorem';
		if (doFoo()) {
			$conditionalArray[] = 2;
			$conditionalArray[] = 3;
			$conditionalInt = 2;
			$conditionalString = 'bar';
			$anotherConditionalString = 'ipsum';
		}

		$unshiftedConditionalArray = $conditionalArray;
		array_unshift($unshiftedConditionalArray, 'lorem', new \stdClass());

		$arrToShift = [1, 2, 3];
		array_shift($arrToShift);

		$arrToPop = [1, 2, 3];
		array_pop($arrToPop);

		$coalesceArray = [];
		$arrayOfUnknownIntegers[42] ?? $coalesceArray[] = 'username';
		$arrayOfUnknownIntegers[108] ?? $coalesceArray[] = 'password';

		$arrayToBeUnset = $array;
		unset($arrayToBeUnset[$string]);

		$arrayToBeUnset2 = $arrayToBeUnset;
		unset($arrayToBeUnset2[$string]);

		$arrayToBeUnset3 = $array;
		unset($arrayToBeUnset3[$integer]);

		$arrayToBeUnset4 = $arrayToBeUnset3;
		unset($arrayToBeUnset4[$integer]);

		/** @var array $shiftedNonEmptyArray */
		$shiftedNonEmptyArray = doFoo();

		if (count($shiftedNonEmptyArray) === 0) {
			return;
		}

		array_shift($shiftedNonEmptyArray);

		/** @var array $unshiftedArray */
		$unshiftedArray = doFoo();
		array_unshift($unshiftedArray, 1);

		/** @var array $poppedNonEmptyArray */
		$poppedNonEmptyArray = doFoo();
		if (count($poppedNonEmptyArray) === 0) {
			return;
		}

		array_pop($poppedNonEmptyArray);

		/** @var array $pushedArray */
		$pushedArray = doFoo();
		array_push($pushedArray, 1);

		$simpleXML = new \SimpleXMLElement('<a><b><c/></b></a>');
		$simpleXMLReturningXML = $simpleXML->asXML();
		if ($simpleXMLReturningXML) {
			$xmlString = $simpleXMLReturningXML;
		}

		$simpleXMLWritingXML = $simpleXML->asXML('path.xml');

		/** @var string $stringForXpath */
		$stringForXpath = doFoo();

		$simpleXMLRightXpath = $simpleXML->xpath('/a/b/c');
		$simpleXMLWrongXpath = $simpleXML->xpath('[foo]');
		$simpleXMLUnknownXpath = $simpleXML->xpath($stringForXpath);

		$namespacedXML = new \SimpleXMLElement('<a><b><c/></b></a>');
		$namespacedXML->registerXPathNamespace('ns', 'namespace');
		$namespacedXpath = $namespacedXML->xpath('/ns:node');

		if (rand(0, 1)) {
			$maybeDefinedVariable = 'foo';
		}

		$sumWithStaticConst = static::INT_CONST + 1;
		$severalSumWithStaticConst1 = static::INT_CONST + 1 + 1;
		$severalSumWithStaticConst2 = 1 + static::INT_CONST + 1;
		$severalSumWithStaticConst3 = 1 + 1 + static::INT_CONST;

		if (!is_array($mixed)) {
			$mixedNoArray = $mixed;
		}
		if (!is_int($mixed)) {
			$mixedNoInt = $mixed;
		}
		if (!is_float($mixed)) {
			$mixedNoFloat = $mixed;
		}
		if (!is_array($mixed)) {
			if (!is_int($mixed)) {
				$mixedNoArrayOrInt = $mixed;
			}
		}

		/** @var int|array $intOrArray */
		$intOrArray = doFoo();

		/** @var array|float $floatOrArray */
		$floatOrArray = doFoo();

		/** @var int|float $intOrFloat */
		$intOrFloat = doFoo();

		/** @var array|float|int|string|bool $plusable */
		$plusable = doFoo();

		assertType('false', true && false);
		assertType('true', true || false);
		assertType('true', true xor false);
		assertType('true', false xor true);
		assertType('false', true xor true);
		assertType('false', true xor true);
		assertType('bool', $bool xor true);
		assertType('bool', $bool xor false);
		assertType('false', true and false);
		assertType('true', true or false);
		assertType('false', !true);
		assertType('-1', -1);
		assertType('1', +1);
		assertType('*ERROR*', +"blabla");
		assertType('123.2', +"123.2");
		assertType('*ERROR*', -"blabla");
		assertType('-5', -5);
		assertType('5', -(-5));
		assertType('9.223372036854776E+18|int', -$integer);
		assertType('-2|-1', -$conditionalInt);
		assertType('*ERROR*', -$string);
		assertType('2', 1 + 1);
		assertType('0', 1 - 1);
		assertType('0.5', 1 / 2);
		assertType('1', 1 * 1);
		assertType('1', 1 ** 1);
		assertType('0', 1 % 1);
		assertType('(float|int)', $integer1 /= 2);
		assertType('int', $integer2 *= 1);
		assertType('2.5999999999999996', 1.2 + 1.4);
		assertType('-0.19999999999999996', 1.2 - 1.4);
		assertType('0.5', 1.2 / 2.4);
		assertType('1.68', 1.2 * 1.4);
		assertType('1.290784508319084', 1.2 ** 1.4);
		assertType('1', 3.2 % 2.4);
		assertType('float', $float1 /= 2.4);
		assertType('float', $float2 *= 2.4);
		assertType('2.4', 1 + 1.4);
		assertType('-0.3999999999999999', 1 - 1.4);
		assertType('0.4166666666666667', 1 / 2.4);
		assertType('1.4', 1 * 1.4);
		assertType('1.0', 1 ** 1.4);
		assertType('1', 3 % 2.4);
		assertType('float', $integer3 /= 2.4);
		assertType('float', $integer4 *= 2.4);
		assertType('int', $otherInteger + 1);
		assertType('float', $otherInteger + 1.0);
		assertType('2.2', 1.2 + 1);
		assertType('0.19999999999999996', 1.2 - 1);
		assertType('0.6', 1.2 / 2);
		assertType('1.2', 1.2 * 1);
		assertType('int', $integer * 10);
		assertType('1.2', 1.2 ** 1);
		assertType('(float|int)', $integer ** $integer);
		assertType('1', 3.2 % 2);
		assertType('int', $float3 %= 2.4);
		assertType('float', $float4 **= 2.4);
		assertType('float', $float5 /= 2.4);
		assertType('float', $float6 *= 2);
		assertType('1', true + false);
		assertType('\'ab\'', 'a' . 'b');
		assertType('\'1b\'', 1 . 'b');
		assertType('\'1b\'', 1.0 . 'b');
		assertType('\'12\'', 1.0 . 2.0);
		assertType('1', 'foo' <=> 'bar');
		assertType('(float|int)', 1 + $mixed);
		assertType('float|int', 1 + $number);
		assertType('float|int', $integer + $number);
		assertType('float', $float + $float);
		assertType('float', $float + $number);
		assertType('(float|int)', 1 / $mixed);
		assertType('float|int', 1 / $number);
		assertType('float', 1.0 / $mixed);
		assertType('float', 1.0 / $number);
		assertType('(float|int)', $mixed / 1);
		assertType('float|int', $number / 1);
		assertType('float', $mixed / 1.0);
		assertType('float', $number / 1.0);
		assertType('float', 1.0 + $mixed);
		assertType('float', 1.0 + $number);
		assertType('(float|int)', $mixed + 1);
		assertType('float|int', $number + 1);
		assertType('float', $mixed + 1.0);
		assertType('float', $number + 1.0);
		assertType('\'foo\'|null', $mixed ? "foo" : null);
		assertType('12', 12 ?: null);
		assertType('1', true ? 1 : 2);
		assertType('2', false ? 1 : 2);
		assertType('12|non-falsy-string', $string ?: 12);
		assertType('12|non-falsy-string', $stringOrNull ?: 12);
		assertType('12|non-falsy-string', @$stringOrNull ?: 12);
		assertType('int<min, -1>|int<1, max>', $integer ?: 12);
		assertType('\'foo\'', 'foo' ?? null);
		assertType('string|null', $stringOrNull ?? null);
		assertType('\'bar\'|\'foo\'', $maybeDefinedVariable ?? 'bar');
		assertType('string', $string ?? 'foo');
		assertType('string', $stringOrNull ?? 'foo');
		assertType('string', $string ?? $integer);
		assertType('int|string', $stringOrNull ?? $integer);
		assertType('\'Foo\'', \Foo::class);
		assertType('106', $line);
		assertType('literal-string&non-falsy-string', $dir);
		assertType('literal-string&non-falsy-string', $file);
		assertType('\'BinaryOperations\\\\NestedNamespace\'', $namespace);
		assertType('\'BinaryOperations\\\\NestedNamespace\\\\Foo\'', $class);
		assertType('\'BinaryOperations\\\\NestedNamespace\\\\Foo::doFoo\'', $method);
		assertType('\'doFoo\'', $function);
		assertType('1', min([1, 2, 3]));
		assertType('array{1, 2, 3}', min([1, 2, 3], [4, 5, 5]));
		assertType('1', min(...[1, 2, 3]));
		assertType('1', min(...[2, 3, 4], ...[5, 1, 8]));
		assertType('0', min(0, ...[1, 2, 3]));
		assertType('array{5, 6, 9}', max([1, 10, 8], [5, 6, 9]));
		assertType('array{1, 1, 1, 1}', max(array(2, 2, 2), array(1, 1, 1, 1)));
		assertType('array<int>', max($arrayOfUnknownIntegers, $arrayOfUnknownIntegers));
		assertType('array{1, 1, 1, 1}', max(array(2, 2, 2), 5, array(1, 1, 1, 1)));
		assertType('array{int, int, int}', max($arrayOfIntegers, 5));
		assertType('array<int>', max($arrayOfUnknownIntegers, 5));
		assertType('array<int>|int', max($arrayOfUnknownIntegers, $integer, $arrayOfUnknownIntegers));
		assertType('array<int>', max($arrayOfUnknownIntegers, $conditionalInt));
		assertType('5', min($arrayOfIntegers, 5));
		assertType('5', min($arrayOfUnknownIntegers, 5));
		assertType('1|2', min($arrayOfUnknownIntegers, $conditionalInt));
		assertType('5', min(array(2, 2, 2), 5, array(1, 1, 1, 1)));
		assertType('1.1', min(...[1.1, 2.2, 3.3]));
		assertType('1.1', min(...[1.1, 2, 3]));
		assertType('3', max(...[1, 2, 3]));
		assertType('3.3', max(...[1.1, 2.2, 3.3]));
		assertType('1', min(1, 2, 3));
		assertType('3', max(1, 2, 3));
		assertType('1.1', min(1.1, 2.2, 3.3));
		assertType('3.3', max(1.1, 2.2, 3.3));
		assertType('1', min(1, 1));
		assertType('*ERROR*', min(1));
		assertType('int|string', min($integer, $string));
		assertType('int|string', min([$integer, $string]));
		assertType('int|string', min(...[$integer, $string]));
		assertType('\'a\'', min('a', 'b'));
		assertType('DateTimeImmutable', max(new \DateTimeImmutable("today"), new \DateTimeImmutable("tomorrow")));
		assertType('1', min(1, 2.2, 3.3));
		assertType('non-falsy-string', "Hello $world");
		assertType('non-falsy-string', $string .= "str");
		assertType('int', $integer5 <<= 2.2);
		assertType('int', $float7 >>= 2.2);
		assertType('3', count($arrayOfIntegers));
		assertType('3', count($arrayOfIntegers, \COUNT_RECURSIVE));
		assertType('3', count($arrayOfIntegers, 5));
		assertType('6', count($arrayOfIntegers) + count($arrayOfIntegers));
		assertType('bool', $string === "foo");
		assertType('true', $fooString === "foo");
		assertType('bool', $string !== "foo");
		assertType('false', $fooString !== "foo");
		assertType('bool', $string == "foo");
		assertType('bool', $string != "foo");
		assertType('true', $foo instanceof \BinaryOperations\NestedNamespace\Foo);
		assertType('bool', $foo instanceof Bar);
		assertType('true', isset($foo));
		assertType('true', isset($foo, $one));
		assertType('false', isset($null));
		assertType('false', isset($undefinedVariable));
		assertType('false', isset($foo, $undefinedVariable));
		assertType('bool', isset($stringOrNull));
		assertType('false', isset($stringOrNull, $null));
		assertType('false', isset($stringOrNull, $undefinedVariable));
		assertType('bool', isset($foo, $stringOrNull));
		assertType('bool', isset($foo, $stringOrNull));
		assertType('true', isset($array['0']));
		assertType('bool', isset($array[$integer]));
		assertType('false', isset($array[$integer], $array[1000]));
		assertType('false', isset($array[$integer], $null));
		assertType('bool', isset($array['0'], $array[$integer]));
		assertType('bool', isset($foo, $array[$integer]));
		assertType('false', isset($foo, $array[1000]));
		assertType('false', isset($foo, $array[1000]));
		assertType('false', !isset($foo));
		assertType('false', empty($foo));
		assertType('true', !empty($foo));
		assertType('array{int, int, int}', $arrayOfIntegers + $arrayOfIntegers);
		assertType('array{int, int, int}', $arrayOfIntegers1 += $arrayOfIntegers);
		assertType('array{1, 1, 1, 1, 1, 2, 3}|array{1, 1, 1, 1, 1}|array{1, 1, 1, 2, 3, 2, 3}|array{1, 1, 1, 2, 3}', $conditionalArray + $unshiftedConditionalArray);
		assertType('array{\'lorem\', stdClass, 1, 1, 1, 2, 3}|array{\'lorem\', stdClass, 1, 1, 1}', $unshiftedConditionalArray + $conditionalArray);
		assertType('array{int, int, int}', $arrayOfIntegers2 += ["foo"]);
		assertType('*ERROR*', $arrayOfIntegers3 += "foo");
		assertType('3', @count($arrayOfIntegers));
		assertType('array{int, int, int}', $anotherArray = $arrayOfIntegers);
		assertType('1', $one1++);
		assertType('1', $one2--);
		assertType('2', ++$one3);
		assertType('0', --$one4);
		assertType('*ERROR*', $preIncArray[0]);
		assertType('1', $preIncArray[1]);
		assertType('2', $preIncArray[2]);
		assertType('*ERROR*', $preIncArray[3]);
		assertType('array{1: 1, 2: 2}', $preIncArray);
		assertType('array{0: 1, 2: 3}', $postIncArray);
		assertType('array{0: array{1: array{2: 3}}, 4: array{5: array{6: 7}}}', $anotherPostIncArray);
		assertType('3', count($array));
		assertType('int<0, max>', count());
		assertType('int<0, max>', count($appendingToArrayInBranches));
		assertType('3|5', count($conditionalArray));
		assertType('2', $array[1]);
		assertType('(float|int)', $integer / $integer);
		assertType('(float|int)', $otherInteger / $integer);
		assertType('(array|float|int)', $mixed + $mixed);
		assertType('(float|int)', $mixed - $mixed);
		assertType('array', $mixed + []);
		assertType('array|int', $intOrArray + $intOrArray);
		assertType('float|int', $intOrFloat + $intOrFloat);
		assertType('array|float', $floatOrArray + $floatOrArray);
		assertType('array|bool|float|int|string', $plusable + $plusable);
		assertType('array', $mixedNoFloat + []);
		assertType('(float|int)', $mixedNoFloat + 5);
		assertType('(float|int)', $mixedNoInt + 5);
		assertType('*ERROR*', $mixedNoArray + []);
		assertType('*ERROR*', $mixedNoArrayOrInt + []);
		assertType('*ERROR*', $integer + []);
		assertType('124', 1 + "123");
		assertType('124.2', 1 + "123.2");
		assertType('*ERROR*', 1 + $string);
		assertType('*ERROR*', 1 + "blabla");
		assertType('array{1, 2, 3}', [1, 2, 3] + [4, 5, 6]);
		assertType('non-empty-array<int>&hasOffsetValue(0, int)&hasOffsetValue(1, int)&hasOffsetValue(2, int)', $arrayOfUnknownIntegers + [1, 2, 3]);
		assertType('(float|int)', $sumWithStaticConst);
		assertType('(float|int)', $severalSumWithStaticConst1);
		assertType('(float|int)', $severalSumWithStaticConst2);
		assertType('(float|int)', $severalSumWithStaticConst3);
		assertType('1', 5 & 3);
		assertType('int<0, 3>', $integer & 3);
		assertType('int<0, 7>', 7 & $integer);
		assertType('int', $integer & $integer);
		assertType('\'x\'', "x" & "y");
		assertType('string', $string & "x");
		assertType('*ERROR*', "bla" & 3);
		assertType('1', "5" & 3);
		assertType('7', 5 | 3);
		assertType('int', $integer | 3);
		assertType('\'y\'', "x" | "y");
		assertType('string', $string | "x");
		assertType('*ERROR*', "bla" | 3);
		assertType('7', "5" | 3);
		assertType('6', 5 ^ 3);
		assertType('int', $integer ^ 3);
		assertType('"\001"', "x" ^ "y");
		assertType('string', $string ^ "x");
		assertType('*ERROR*', "bla" ^ 3);
		assertType('6', "5" ^ 3);
		assertType('int<0, 3>', $integer6 &= 3);
		assertType('*ERROR*', $string &= 3);
		assertType('string', $string &= "x");
		assertType('int', $integer7 |= 3);
		assertType('*ERROR*', $string |= 3);
		assertType('string', $string |= "x");
		assertType('int', $integer8 ^= 3);
		assertType('*ERROR*', $string ^= 3);
		assertType('string', $string ^= "x");
		assertType('\'f\'', $fooString[0]);
		assertType('*ERROR*', $fooString[4]);
		assertType('\'\'|\'f\'|\'o\'', $fooString[$integer]);
		assertType('\'foo   bar\'', $foobarString);
		assertType('\'foo bar\'', "$fooString bar");
		assertType('non-falsy-string', "$std bar");
		assertType('non-empty-array<\'foo\'|int|stdClass>', $arrToPush);
		assertType('non-empty-array<\'foo\'|int|stdClass>', $arrToPush2);
		assertType('array{0: \'lorem\', 1: 5, foo: stdClass, 2: \'test\'}', $arrToUnshift);
		assertType('non-empty-array<\'lorem\'|int|stdClass>', $arrToUnshift2);
		assertType('array{\'lorem\', stdClass, 1, 1, 1, 2, 3}|array{\'lorem\', stdClass, 1, 1, 1}', $unshiftedConditionalArray);
		assertType('array{dirname?: string, basename: string, extension?: string, filename: string}', pathinfo($string));
		assertType('string', pathinfo($string, PATHINFO_DIRNAME));
		assertType('string', $string1++);
		assertType('string', $string2--);
		assertType('(float|int|string)', ++$string3);
		assertType('(float|int|string)', --$string4);
		assertType('(float|int|string)', $incrementedString);
		assertType('(float|int|string)', $decrementedString);
		assertType('\'foo\'', $fooString1++);
		assertType('\'foo\'', $fooString2--);
		assertType('\'fop\'', ++$fooString3);
		assertType('\'fon\'', --$fooString4);
		assertType('\'fop\'', $incrementedFooString);
		assertType('\'fon\'', $decrementedFooString);
		assertType('\'barbar\'|\'barfoo\'|\'foobar\'|\'foofoo\'', $conditionalString . $conditionalString);
		assertType('\'baripsum\'|\'barlorem\'|\'fooipsum\'|\'foolorem\'', $conditionalString . $anotherConditionalString);
		assertType('\'ipsumbar\'|\'ipsumfoo\'|\'lorembar\'|\'loremfoo\'', $anotherConditionalString . $conditionalString);
		assertType('6|8', count($conditionalArray) + count($array));
		assertType('bool', is_numeric($string));
		assertType('false', is_numeric($fooString));
		assertType('bool', is_int($mixed));
		assertType('true', is_int($integer));
		assertType('false', is_int($string));
		assertType('bool', in_array('foo', ['foo', 'bar']));
		assertType('true', in_array('foo', ['foo', 'bar'], true));
		assertType('false', in_array('baz', ['foo', 'bar'], true));
		assertType('array{2, 3}', $arrToShift);
		assertType('array{1, 2}', $arrToPop);
		assertType('class-string<static(BinaryOperations\NestedNamespace\Foo)>', static::class);
		assertType('\'BinaryOperations\\\\NestedNamespace\\\\NonexistentClass\'', NonexistentClass::class);
		assertType('class-string', parent::class);
		assertType('true', array_key_exists(0, $array));
		assertType('false', array_key_exists(3, $array));
		assertType('bool', array_key_exists(3, $conditionalArray));
		assertType('bool', array_key_exists('foo', $generalArray));
		assertType('string', sprintf($string, $string, 1));
		assertType('\'foo bar\'', sprintf('%s %s', 'foo', 'bar'));
		assertType('array{}|array{\'password\'}|array{0: \'username\', 1?: \'password\'}', $coalesceArray);
		assertType('array{1, 2, 3}', $arrayToBeUnset);
		assertType('array{1, 2, 3}', $arrayToBeUnset2);
		assertType('array{0?: 1, 1?: 2, 2?: 3}', $arrayToBeUnset3);
		assertType('array{0?: 1, 1?: 2, 2?: 3}', $arrayToBeUnset4);
		assertType('array', $shiftedNonEmptyArray);
		assertType('non-empty-array', $unshiftedArray);
		assertType('array', $poppedNonEmptyArray);
		assertType('non-empty-array', $pushedArray);
		assertType('string|false', $simpleXMLReturningXML);
		assertType('non-falsy-string', $xmlString);
		assertType('bool', $simpleXMLWritingXML);
		assertType('array<SimpleXMLElement>|null', $simpleXMLRightXpath);
		assertType('array<SimpleXMLElement>|false|null', $simpleXMLWrongXpath);
		assertType('array<SimpleXMLElement>|false|null', $simpleXMLUnknownXpath);
		assertType('array<SimpleXMLElement>|false|null', $namespacedXpath);
	}

}
