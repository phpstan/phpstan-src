<?php // lint >= 8.4

namespace LooseEqualityNarrowing;

use function PHPStan\Testing\assertType;

class Foo
{
}

enum Suit
{
	case Hearts;
	case Spades;
}

class Bar
{

	/**
	 * @param int<0, 10>|string $intOrString
	 * @param 1|2|'a'|null $constants
	 * @param int|float|string|bool|null $scalar
	 * @param float<1, 3> $floatRange
	 * @param numeric-string $numericString
	 * @param int|string|array<mixed> $intStringOrArray
	 * @param array<mixed> $array
	 */
	public function doFoo(mixed $mixed, int $int, float $float, string $string, bool $bool, $intOrString, $constants, Foo|int $fooOrInt, $scalar, float $floatRange, string $numericString, $intStringOrArray, array $array, Suit $suit): void
	{
		// mixed would only become a union with objects it says nothing about,
		// so it only loses null, which cannot equal the value
		if ($mixed == 'bar') {
			assertType('mixed~null', $mixed);
		} else {
			assertType("mixed~('bar'|true)", $mixed);
		}

		if ($mixed == 5) {
			assertType('mixed~null', $mixed);
		}

		if ($mixed == NAN) {
			assertType('true', $mixed);
		}

		if ($int == '5') {
			assertType('5', $int);
		} else {
			assertType('int<min, 4>|int<6, max>', $int);
		}

		if ($int == 1.5) {
			assertType('*NEVER*', $int);
		}

		if ($float == 1) {
			assertType('1.0', $float);
		}

		if ($string == 5) {
			assertType('numeric-string', $string);
		}

		if ($bool == 'abc') {
			assertType('true', $bool);
		}

		if ($intOrString == 'abc') {
			assertType("'abc'", $intOrString);
		} else {
			assertType('int<0, 10>|string', $intOrString);
		}

		if ($intOrString == 3) {
			assertType('3|numeric-string', $intOrString);
		} else {
			assertType('int<0, 2>|int<4, 10>|string', $intOrString);
		}

		if ($constants == 'a') {
			assertType("'a'", $constants);
		} else {
			assertType('1|2|null', $constants);
		}

		if ($constants == 0) {
			assertType('null', $constants);
		} else {
			assertType("1|2|'a'", $constants);
		}

		if ($fooOrInt == 1) {
			assertType('1|LooseEqualityNarrowing\Foo', $fooOrInt);
		}

		// only GMP, BcMath\Number and SimpleXMLElement objects compare as their number
		if ($fooOrInt == 2) {
			assertType('2', $fooOrInt);
		}

		if ($scalar == 'x') {
			assertType("'x'|true", $scalar);
		}

		if ($int == $floatRange) {
			assertType('int<1, 3>', $int);
		}

		if ($int == $float) {
			assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $float);
		}

		if ($numericString == $intStringOrArray) {
			assertType('int|numeric-string', $intStringOrArray);
		}

		if ($suit == Suit::Hearts) {
			assertType('LooseEqualityNarrowing\Suit::Hearts', $suit);
		} else {
			assertType('LooseEqualityNarrowing\Suit::Spades', $suit);
		}

		if (in_array($bool, [Suit::Hearts])) {
			assertType('true', $bool);
		}

		if (in_array($string, ['1', 'a'])) {
			assertType("'a'|numeric-string", $string);
		}

		if ($numericString == '1') {
			assertType('numeric-string', $numericString);
		} else {
			assertType('numeric-string', $numericString);
		}

		if ((int) $string) {
			assertType('non-falsy-string', $string);
		} else {
			assertType('string', $string);
		}

		if ((string) $int) {
			assertType('int<min, -1>|int<1, max>', $int);
		} else {
			assertType('0', $int);
		}

		if ((int) $float) {
			assertType('float', $float);
		} else {
			assertType('float', $float);
		}

		assertType('false', $float == 'a');
		assertType('false', $array == 'abc');
		assertType('false', $int == 'abc');
		assertType('bool', $fooOrInt == 'abc');
		assertType('false', $float == NAN);
		assertType('false', $constants == 'b');
		assertType('true', $constants == true || $constants == false || $constants == null);
	}

}

/**
 * @template T
 * @param T $t
 * @param non-empty-mixed $nonEmptyMixed
 * @param array<string, mixed> $array
 * @param string|bool|null $stringBoolOrNull
 * @param object|string $objectOrString
 */
function mixedStaysMixed($t, $nonEmptyMixed, array $array, $stringBoolOrNull, $objectOrString): void
{
	if ($t == 'a') {
		assertType('T of mixed~null (function LooseEqualityNarrowing\\mixedStaysMixed(), argument)', $t);
	}
	if ($nonEmptyMixed == 'a') {
		assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $nonEmptyMixed);
	}
	if ($array['x'] == 'a') {
		assertType('mixed~null', $array['x']);
	}
	if (in_array($array['y'], ['a', 'b'])) {
		assertType('mixed~null', $array['y']);
	}
	switch ($array['z']) {
		case 'a':
			assertType('mixed~null', $array['z']);
			break;
	}

	// a set without objects still narrows mixed
	if ($array['n'] == null) {
		assertType("0|0.0|''|array{}|false|null", $array['n']);
	}

	// other types are narrowed, objects to the ones that can equal the value
	if ($stringBoolOrNull == 'a') {
		assertType("'a'|true", $stringBoolOrNull);
	}
	if ($objectOrString == 'a') {
		assertType("'a'|GMP|Stringable", $objectOrString);
	}
}
