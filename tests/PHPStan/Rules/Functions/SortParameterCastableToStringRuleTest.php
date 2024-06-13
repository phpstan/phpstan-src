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
		return new SortParameterCastableToStringRule($broker, new ParameterCastableToStringCheck(new RuleLevelHelper($broker, true, false, true, false, false, true, false)));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/param-castable-to-string-functions.php'], $this->hackParameterNames([
			[
				'Parameter #1 $array of function array_unique expects an array of values castable to string, array<int, array<int, string>> given.',
				22,
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
			[
				'Parameter #1 $array of function arsort expects an array of values castable to float, array<int, ParamCastableToStringFunctions\ClassWithToString> given.',
				42,
			],
			[
				'Parameter #1 $array of function arsort expects an array of values castable to string and float, array<int, ParamCastableToStringFunctions\ClassWithToString> given.',
				43,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string, array<int, array<int, string>> given.',
				44,
			],
			[
				'Parameter #1 $array of function sort expects an array of values castable to string and float, array<int, array<int, string>> given.',
				45,
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
		]);
	}

	public function testEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/param-castable-to-string-functions-enum.php'], [
			[
				'Parameter #1 $array of function array_unique expects an array of values castable to string, array<int, ParamCastableToStringFunctionsEnum\\FooEnum::A|string> given.',
				18,
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
