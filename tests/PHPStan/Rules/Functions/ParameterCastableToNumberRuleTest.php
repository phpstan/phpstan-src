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
 * @extends RuleTestCase<ParameterCastableToNumberRule>
 */
class ParameterCastableToNumberRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ParameterCastableToNumberRule($broker, new ParameterCastableToStringCheck(new RuleLevelHelper($broker, true, false, true, true, true, false)));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/param-castable-to-number-functions.php'], $this->hackPhp74ErrorMessages([
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int, array<int, int>> given.',
				20,
			],
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int, stdClass> given.',
				21,
			],
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int, string> given.',
				22,
			],
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int, resource|false> given.',
				23,
			],
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int, CurlHandle> given.',
				24,
			],
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int, ParamCastableToNumberFunctions\\ClassWithToString> given.',
				25,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int, array<int, int>> given.',
				27,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int, stdClass> given.',
				28,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int, string> given.',
				29,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int, resource|false> given.',
				30,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int, CurlHandle> given.',
				31,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int, ParamCastableToNumberFunctions\\ClassWithToString> given.',
				32,
			],
		]));
	}

	public function testNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/param-castable-to-number-functions-named-args.php'], [
			[
				'Parameter $array of function array_sum expects an array of values castable to number, array<int, array<int, int>> given.',
				7,
			],
			[
				'Parameter $array of function array_product expects an array of values castable to number, array<int, array<int, int>> given.',
				8,
			],
		]);
	}

	public function testEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/param-castable-to-number-functions-enum.php'], [
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int, ParamCastableToNumberFunctionsEnum\\FooEnum::A> given.',
				12,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int, ParamCastableToNumberFunctionsEnum\\FooEnum::A> given.',
				13,
			],
		]);
	}

	public function testBug11883(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-11883.php'], [
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int, Bug11883\\SomeEnum::A|Bug11883\\SomeEnum::B> given.',
				13,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int, Bug11883\\SomeEnum::A|Bug11883\\SomeEnum::B> given.',
				14,
			],
		]);
	}

	public function testBug12146(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12146.php'], $this->hackPhp74ErrorMessages([
			[
				'Parameter #1 $array of function array_sum expects an array of values castable to number, array<int|stdClass> given.',
				16,
			],
			[
				'Parameter #1 $array of function array_product expects an array of values castable to number, array<int|stdClass> given.',
				22,
			],
		]));
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string|null}> $errors
	 * @return list<array{0: string, 1: int, 2?: string|null}>
	 */
	private function hackPhp74ErrorMessages(array $errors): array
	{
		if (PHP_VERSION_ID >= 80000) {
			return $errors;
		}

		return array_map(static function (array $error): array {
			$error[0] = str_replace(
				[
					'$array of function array_sum',
					'$array of function array_product',
					'array<int, CurlHandle>',
				],
				[
					'$input of function array_sum',
					'$input of function array_product',
					'array<int, resource>',
				],
				$error[0],
			);

			return $error;
		}, $errors);
	}

}
