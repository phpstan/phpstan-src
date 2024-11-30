<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\ParameterCastableToStringCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ImplodeParameterCastableToStringRule>
 */
class ImplodeParameterCastableToStringRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ImplodeParameterCastableToStringRule($broker, new ParameterCastableToStringCheck(new RuleLevelHelper($broker, true, false, true, true, true, false)));
	}

	public function testNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/implode-param-castable-to-string-functions-named-args.php'], [
			[
				'Parameter $array of function implode expects array<string>, array<int, array<int, string>> given.',
				8,
			],
			[
				'Parameter $separator of function implode expects array<string>, array<int, array<int, string>> given.',
				9,
			],
			[
				'Parameter $array of function implode expects array<string>, array<int, array<int, string>> given.',
				10,
			],
			[
				'Parameter $array of function implode expects array<string>, array<int, array<int, string>> given.',
				11,
			],
		]);
	}

	public function testEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/implode-param-castable-to-string-functions-enum.php'], [
			[
				'Parameter #2 $array of function implode expects array<string>, array<int, ImplodeParamCastableToStringFunctionsEnum\\FooEnum::A> given.',
				12,
			],
		]);
	}

	public function testImplode(): void
	{
		$this->analyse([__DIR__ . '/data/implode.php'], [
			[
				'Parameter #2 $array of function implode expects array<string>, array<int, list<string>|string> given.',
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

	public function testBug12146(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12146.php'], [
			[
				'Parameter #2 $array of function implode expects array<string>, array<int|stdClass> given.',
				28,
			],
		]);
	}

}
