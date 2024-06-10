<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_map;
use function str_replace;
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
		$this->analyse([__DIR__ . '/data/param-castable-to-string-functions.php'], $this->hackParameterNames([
			[
				'Parameter #1 $array of function array_intersect expects an array of values castable to string, array<int, array> given.',
				16,
			],
			[
				'Parameter #2 $arrays of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctions\\ClassWithoutToString> given.',
				17,
			],
			[
				'Parameter #3 of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctions\\ClassWithoutToString> given.',
				18,
			],
			[
				'Parameter #2 $arrays of function array_diff expects an array of values castable to string, array<int, ParamCastableToStringFunctions\\ClassWithoutToString> given.',
				19,
			],
			[
				'Parameter #2 $arrays of function array_diff_assoc expects an array of values castable to string, array<int, ParamCastableToStringFunctions\\ClassWithoutToString> given.',
				20,
			],
			[
				'Parameter #1 $array of function array_unique expects an array of values castable to string, array<int, array<int, string>> given.',
				22,
			],
			[
				'Parameter #1 $keys of function array_combine expects an array of values castable to string, array<int, array<int, string>> given.',
				23,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, array<int, string>> given.',
				26,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, ParamCastableToStringFunctions\\ClassWithoutToString> given.',
				27,
			],
			[
				'Parameter #1 $array of function rsort expects an array of values castable to string, array<int, array<int, string>> given.',
				28,
			],
			[
				'Parameter #1 $array of function asort expects an array of values castable to string, array<int, array<int, string>> given.',
				29,
			],
			[
				'Parameter #1 $array of function arsort expects an array of values castable to string, array<int, array<int, string>> given.',
				30,
			],
			[
				'Parameter #1 $array of function natsort expects an array of values castable to string, array<int, array<int, string>> given.',
				31,
			],
			[
				'Parameter #1 $array of function natcasesort expects an array of values castable to string, array<int, array<int, string>> given.',
				32,
			],
			[
				'Parameter #1 $array of function array_count_values expects an array of values castable to string, array<int, array<int, string>> given.',
				33,
			],
			[
				'Parameter #1 $keys of function array_fill_keys expects an array of values castable to string, array<int, array<int, string>> given.',
				34,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, array<int, string>> given.',
				36,
			],
			[
				'Parameter #1 $array of function rsort expects an array of values castable to string, array<int, array<int, string>> given.',
				37,
			],
			[
				'Parameter #1 $array of function asort expects an array of values castable to string, array<int, array<int, string>> given.',
				38,
			],
		]));
	}

	public function testNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/param-castable-to-string-functions-named-args.php'], [
			[
				'Parameter $array of function array_unique expects an array of values castable to string, array<int, array<int, string>> given.',
				16,
			],
			[
				'Parameter $keys of function array_combine expects an array of values castable to string, array<int, array<int, string>> given.',
				17,
			],
			[
				'Parameter $array of function sort expects an array of values castable to string, array<int, array<int, string>> given.',
				19,
			],
			[
				'Parameter $array of function rsort expects an array of values castable to string, array<int, array<int, string>> given.',
				20,
			],
			[
				'Parameter $array of function asort expects an array of values castable to string, array<int, array<int, string>> given.',
				21,
			],
			[
				'Parameter $array of function arsort expects an array of values castable to string, array<int, array<int, string>> given.',
				22,
			],
			[
				'Parameter $keys of function array_fill_keys expects an array of values castable to string, array<int, array<int, string>> given.',
				23,
			],
			[
				'Parameter $array of function implode expects array<string>, array<int, array<int, string>> given.',
				25,
			],
			[
				'Parameter $separator of function implode expects array<string>, array<int, array<int, string>> given.',
				26,
			],
			[
				'Parameter $array of function implode expects array<string>, array<int, array<int, string>> given.',
				27,
			],
			[
				'Parameter $array of function implode expects array<string>, array<int, array<int, string>> given.',
				28,
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
				'Parameter #1 $array of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				12,
			],
			[
				'Parameter #2 $arrays of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				13,
			],
			[
				'Parameter #3 of function array_intersect expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				14,
			],
			[
				'Parameter #2 $arrays of function array_diff expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				15,
			],
			[
				'Parameter #2 $arrays of function array_diff_assoc expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				16,
			],
			[
				'Parameter #1 $array of function array_unique expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A|string> given.',
				18,
			],
			[
				'Parameter #1 $keys of function array_combine expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				19,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				21,
			],
			[
				'Parameter #1 $array of function rsort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				22,
			],
			[
				'Parameter #1 $array of function asort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				23,
			],
			[
				'Parameter #1 $array of function arsort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				24,
			],
			[
				'Parameter #1 $array of function natsort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				25,
			],
			[
				'Parameter #1 $array of function natcasesort expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				26,
			],
			[
				'Parameter #1 $array of function array_count_values expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				27,
			],
			[
				'Parameter #1 $keys of function array_fill_keys expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum> given.',
				28,
			],
			[
				'Parameter #2 $array of function implode expects array<string>, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				31,
			],
		]);
	}

	public function testImplode(): void
	{
		$this->analyse([__DIR__ . '/data/implode.php'], $this->hackParameterNames([
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
		]));
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
		$this->analyse([__DIR__ . '/data/bug-5848.php'], $this->hackParameterNames([
			[
				'Parameter #1 $array of function array_diff expects an array of values castable to string, array<int, stdClass> given.',
				8,
			],
			[
				'Parameter #2 $arrays of function array_diff expects an array of values castable to string, array<int, stdClass> given.',
				8,
			],
		]));
	}

	public function testBug3946(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3946.php'], [
			[
				'Parameter #1 $keys of function array_combine expects an array of values castable to string, array<int, array<int, string>|Bug3946\stdClass|float|int|string> given.',
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
				'Parameter #1 $keys of function array_fill_keys expects an array of values castable to string, array<Bug11111\\Language> given.',
				23,
			],
			[
				'Parameter #1 $keys of function array_fill_keys expects an array of values castable to string, array<int, Bug11111\\Language::DUT|Bug11111\\Language::ITA> given.',
				26,
			],
		]);
	}

	public function testBug11141(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-11141.php'], [
			[
				'Parameter #1 $array of function array_diff expects an array of values castable to string, array<int, Bug11141\\Language::DAN|Bug11141\\Language::ENG|Bug11141\\Language::GER> given.',
				22,
			],
			[
				'Parameter #2 $arrays of function array_diff expects an array of values castable to string, array<int, Bug11141\\Language::DAN> given.',
				22,
			],
		]);
	}

	public function testBug11167(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11167.php'], []);
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string|null}> $errors
	 * @return list<array{0: string, 1: int, 2?: string|null}>
	 */
	private function hackParameterNames(array $errors): array
	{
		if (PHP_VERSION_ID >= 80000) {
			return $errors;
		}

		return array_map(static function (array $error): array {
			$error[0] = str_replace(
				[
					'$array of function array_diff',
					'$array of function array_diff_assoc',
					'$array of function array_intersect',
					'$arrays of function array_intersect',
					'$arrays of function array_diff',
					'$arrays of function array_diff_assoc',
					'$array of function sort',
					'$array of function rsort',
					'$array of function asort',
					'$array of function arsort',
					'$array of function natsort',
					'$array of function natcasesort',
					'$array of function array_count_values',
					'#3 of function array_intersect',
				],
				[
					'$arr1 of function array_diff',
					'$arr1 of function array_diff_assoc',
					'$arr1 of function array_intersect',
					'$arr2 of function array_intersect',
					'$arr2 of function array_diff',
					'$arr2 of function array_diff_assoc',
					'$array_arg of function sort',
					'$array_arg of function rsort',
					'$array_arg of function asort',
					'$array_arg of function arsort',
					'$array_arg of function natsort',
					'$array_arg of function natcasesort',
					'$input of function array_count_values',
					'#3 $args of function array_intersect',
				],
				$error[0],
			);

			return $error;
		}, $errors);
	}

}
