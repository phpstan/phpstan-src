<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ReturnTypeRule>
 */
class ReturnTypeRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		[, $functionReflector] = self::getReflectors();
		return new ReturnTypeRule(new FunctionReturnTypeCheck(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false)), $functionReflector);
	}

	public function testReturnTypeRule(): void
	{
		require_once __DIR__ . '/data/returnTypes.php';
		$this->analyse([__DIR__ . '/data/returnTypes.php'], [
			[
				'Function ReturnTypes\returnInteger() should return int but returns string.',
				17,
			],
			[
				'Function ReturnTypes\returnObject() should return ReturnTypes\Bar but returns int.',
				27,
			],
			[
				'Function ReturnTypes\returnObject() should return ReturnTypes\Bar but returns ReturnTypes\Foo.',
				31,
			],
			[
				'Function ReturnTypes\returnChild() should return ReturnTypes\Foo but returns ReturnTypes\OtherInterfaceImpl.',
				50,
			],
			[
				'Function ReturnTypes\returnVoid() with return type void returns null but should not return anything.',
				83,
			],
			[
				'Function ReturnTypes\returnVoid() with return type void returns int but should not return anything.',
				87,
			],
			[
				'Function ReturnTypes\returnFromGeneratorString() should return string but empty return statement found.',
				152,
			],
			[
				'Function ReturnTypes\returnFromGeneratorString() should return string but returns int.',
				155,
			],
			[
				'Function ReturnTypes\returnVoidFromGenerator2() with return type void returns int but should not return anything.',
				173,
			],
			[
				'Function ReturnTypes\returnNever() should never return but return statement found.',
				181,
			],
		]);
	}

	public function testReturnTypeRulePhp70(): void
	{
		$this->analyse([__DIR__ . '/data/returnTypes-7.0.php'], [
			[
				'Function ReturnTypes\Php70\returnInteger() should return int but empty return statement found.',
				7,
			],
		]);
	}

	public function testIsGenerator(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->analyse([__DIR__ . '/data/is-generator.php'], []);
	}

	public function testBug2568(): void
	{
		require_once __DIR__ . '/data/bug-2568.php';
		$this->analyse([__DIR__ . '/data/bug-2568.php'], []);
	}

}
