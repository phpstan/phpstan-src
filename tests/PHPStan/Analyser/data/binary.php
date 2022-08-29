<?php

namespace BinaryOperations\NestedNamespace;

class Foo
{

	public const INT_CONST = 1;

	public function doFoo(array $generalArray)
	{
		/** @var float $float */
		$float = doFoo();

		/** @var int $integer */
		$integer = doFoo();

		/** @var bool $bool */
		$bool = doFoo();

		/** @var string $string */
		$string = doFoo();

		$fooString = 'foo';

		/** @var string|null $stringOrNull */
		$stringOrNull = doFoo();

		$arrayOfIntegers = [$integer, $integer + 1, $integer + 2];

		$foo = new Foo();

		$one = 1;

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

		/** @var int|array $intOrArray */
		$intOrArray = doFoo();

		die;
	}

}
