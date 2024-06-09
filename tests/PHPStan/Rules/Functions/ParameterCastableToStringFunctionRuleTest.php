<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ParameterCastableToStringFunctionRule>
 */
class ParameterCastableToStringFunctionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ParameterCastableToStringFunctionRule($broker, new RuleLevelHelper($broker, true, false, true, false, false, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/param-castable-to-string-functions.php'], [
			[
				'Parameter #1 of function array_intersect expects an array of values castable to string, array<int, array> given.',
				16,
			],
			[
				'Parameter #2 of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctions\ClassWithoutToString> given.',
				17,
			],
			[
				'Parameter #3 of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctions\ClassWithoutToString> given.',
				18,
			],
			[
				'Parameter #2 of function array_diff expects an array of values castable to string, array<int, ParamCastableToStringFunctions\ClassWithoutToString> given.',
				19,
			],
			[
				'Parameter #2 of function array_diff_assoc expects an array of values castable to string, array<int, ParamCastableToStringFunctions\ClassWithoutToString> given.',
				20,
			],
			[
				'Parameter #1 of function array_unique expects an array of values castable to string, array<int, array<int, string>> given.',
				22,
			],
			[
				'Parameter #1 of function array_combine expects an array of values castable to string, array<int, array<int, string>> given.',
				23,
			],
			[
				'Parameter #1 of function sort expects an array of values castable to string, array<int, array<int, string>> given.',
				26,
			],
			[
				'Parameter #1 of function sort expects an array of values castable to string, array<int, ParamCastableToStringFunctions\ClassWithoutToString> given.',
				27,
			],
			[
				'Parameter #1 of function rsort expects an array of values castable to string, array<int, array<int, string>> given.',
				28,
			],
			[
				'Parameter #1 of function asort expects an array of values castable to string, array<int, array<int, string>> given.',
				29,
			],
			[
				'Parameter #1 of function arsort expects an array of values castable to string, array<int, array<int, string>> given.',
				30,
			],
			[
				'Parameter #1 of function natsort expects an array of values castable to string, array<int, array<int, string>> given.',
				31,
			],
			[
				'Parameter #1 of function natcasesort expects an array of values castable to string, array<int, array<int, string>> given.',
				32,
			],
			[
				'Parameter #1 of function array_count_values expects an array of values castable to string, array<int, array<int, string>> given.',
				33,
			],
			[
				'Parameter #1 of function array_fill_keys expects an array of values castable to string, array<int, array<int, string>> given.',
				34,
			],
			[
				'Parameter #1 of function array_flip expects an array of values castable to string, array<int, array<int, string>> given.',
				35,
			],
		]);
	}

	public function testEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/param-castable-to-string-functions-enum.php'], [
			[
				'Parameter #1 of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				12,
			],
			[
				'Parameter #2 of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				13,
			],
			[
				'Parameter #3 of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				14,
			],
			[
				'Parameter #2 of function array_diff expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				15,
			],
			[
				'Parameter #2 of function array_diff_assoc expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				16,
			],
			[
				'Parameter #1 of function array_unique expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A|string> given.',
				18,
			],
			[
				'Parameter #1 of function array_combine expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				19,
			],
			[
				'Parameter #1 of function sort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				21,
			],
			[
				'Parameter #1 of function rsort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				22,
			],
			[
				'Parameter #1 of function asort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				23,
			],
			[
				'Parameter #1 of function arsort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				24,
			],
			[
				'Parameter #1 of function natsort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				25,
			],
			[
				'Parameter #1 of function natcasesort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				26,
			],
			[
				'Parameter #1 of function array_count_values expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				27,
			],
			[
				'Parameter #1 of function array_fill_keys expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				28,
			],
			[
				'Parameter #1 of function array_flip expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				29,
			],
			[
				'Parameter #2 $array of function implode expects array<string>, array<int, ParamCastableToStringFunctionsEnum\FooEnum::A> given.',
				31,
			],
		]);
	}

	public function testImplode(): void
	{
		$this->analyse([__DIR__ . '/data/implode.php'], [
			[
				'Parameter #2 $array of function implode expects array<string>, array<int, array<int, string>|string> given.',
				9,
			],
			[
				'Parameter #1 $array of function implode expects array<string>, array<int, array<int, string>> given.',
				11,
			],
			[
				'Parameter #1 $array of function implode expects array<string>, array<int, array<int, int>> given.',
				12,
			],
			[
				'Parameter #1 $array of function implode expects array<string>, array<int, array<int, int|true>> given.',
				13,
			],
			[
				'Parameter #2 $array of function implode expects array<string>, array<int, array<int, string>> given.',
				15,
			],
			[
				'Parameter #2 $array of function join expects array<string>, array<int, array<int, string>> given.',
				16,
			],
		]);
	}

	public function testBug6000(): void
	{
		$this->analyse([__DIR__ . '/../Arrays/data/bug-6000.php'], []);
	}

	public function testBug8467a(): void
	{
		$this->analyse([__DIR__ . '/../Arrays/data/bug-8467a.php'], []);
	}

	public function testBug5848(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5848.php'], [
			[
				'Parameter #1 of function array_diff expects an array of values castable to string, array<int, stdClass> given.',
				8,
			],
			[
				'Parameter #2 of function array_diff expects an array of values castable to string, array<int, stdClass> given.',
				8,
			],
		]);
	}

	public function testBug3946(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3946.php'], [
			[
				'Parameter #1 of function array_combine expects an array of values castable to string, array<int, array<int, string>|Bug3946\stdClass|float|int|string> given.',
				8,
			],
		]);
	}

	public function testBug11111(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-11111.php'], [
			[
				'Parameter #1 of function array_fill_keys expects an array of values castable to string, array<Bug11111\\Language> given.',
				23,
			],
			[
				'Parameter #1 of function array_fill_keys expects an array of values castable to string, array<int, Bug11111\\Language::DUT|Bug11111\\Language::ITA> given.',
				26,
			],
		]);
	}

	public function testBug11114(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-11114.php'], [
			[
				'Parameter #1 of function array_diff expects an array of values castable to string, array<int, Bug11114\\Language::DAN|Bug11114\\Language::ENG|Bug11114\\Language::GER> given.',
				22,
			],
			[
				'Parameter #2 of function array_diff expects an array of values castable to string, array<int, Bug11114\\Language::DAN> given.',
				22,
			],
		]);
	}

}
