<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\ParameterCastableToStringCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_map;
use function str_replace;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<SortParameterCastableToStringRule>
 */
class SortParameterCastableToStringRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new SortParameterCastableToStringRule($broker, new ParameterCastableToStringCheck(new RuleLevelHelper($broker, true, false, true, true, true, false)));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/sort-param-castable-to-string-functions.php'], $this->hackParameterNames([
			[
				'Parameter #1 $array of function array_unique expects an array of values castable to string, array<int, list<string>> given.',
				16,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, array<int, string>> given.',
				19,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, SortParamCastableToStringFunctions\\ClassWithoutToString> given.',
				20,
			],
			[
				'Parameter #1 $array of function rsort expects an array of values castable to string, list<array<int, string>> given.',
				21,
			],
			[
				'Parameter #1 $array of function asort expects an array of values castable to string, list<array<int, string>> given.',
				22,
			],
			[
				'Parameter #1 $array of function arsort expects an array of values castable to string, array<int, list<string>> given.',
				23,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, list<string>> given.',
				25,
			],
			[
				'Parameter #1 $array of function rsort expects an array of values castable to string, list<array<int, string>> given.',
				26,
			],
			[
				'Parameter #1 $array of function asort expects an array of values castable to string, list<array<int, string>> given.',
				27,
			],
			[
				'Parameter #1 $array of function arsort expects an array of values castable to float, array<int, SortParamCastableToStringFunctions\ClassWithToString> given.',
				31,
			],
			[
				'Parameter #1 $array of function arsort expects an array of values castable to string and float, array<int, SortParamCastableToStringFunctions\ClassWithToString> given.',
				32,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, list<string>> given.',
				33,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string and float, list<array<int, string>> given.',
				34,
			],
		]));
	}

	public function testNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/sort-param-castable-to-string-functions-named-args.php'], [
			[
				'Parameter $array of function array_unique expects an array of values castable to string, array<int, list<string>> given.',
				7,
			],
			[
				'Parameter $array of function sort expects an array of values castable to string, array<int, array<int, string>> given.',
				9,
			],
			[
				'Parameter $array of function rsort expects an array of values castable to string, list<array<int, string>> given.',
				10,
			],
			[
				'Parameter $array of function asort expects an array of values castable to string, list<array<int, string>> given.',
				11,
			],
			[
				'Parameter $array of function arsort expects an array of values castable to string, array<int, list<string>> given.',
				12,
			],
		]);
	}

	public function testEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/sort-param-castable-to-string-functions-enum.php'], [
			[
				'Parameter #1 $array of function array_unique expects an array of values castable to string, array<int, SortParamCastableToStringFunctionsEnum\\FooEnum::A|string> given.',
				12,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, SortParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				14,
			],
			[
				'Parameter #1 $array of function rsort expects an array of values castable to string, list<SortParamCastableToStringFunctionsEnum\FooEnum::A> given.',
				15,
			],
			[
				'Parameter #1 $array of function asort expects an array of values castable to string, list<SortParamCastableToStringFunctionsEnum\FooEnum::A> given.',
				16,
			],
			[
				'Parameter #1 $array of function arsort expects an array of values castable to string, array<int, SortParamCastableToStringFunctionsEnum\\FooEnum> given.',
				17,
			],
		]);
	}

	public function testBug11167(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11167.php'], []);
	}

	public function testBug12146(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12146.php'], $this->hackParameterNames([
			[
				'Parameter #1 $array of function array_unique expects an array of values castable to string, array<int|stdClass> given.',
				46,
			],
		]));
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
					'$array of function sort',
					'$array of function rsort',
					'$array of function asort',
					'$array of function arsort',
				],
				[
					'$array_arg of function sort',
					'$array_arg of function rsort',
					'$array_arg of function asort',
					'$array_arg of function arsort',
				],
				$error[0],
			);

			return $error;
		}, $errors);
	}

}
