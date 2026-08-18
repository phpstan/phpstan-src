<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ParameterOutExecutionEndTypeRule>
 */
class ParameterOutExecutionEndTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ParameterOutExecutionEndTypeRule(
			new ParameterOutTypeCheck(
				new RuleLevelHelper(
					self::createReflectionProvider(),
					checkNullables: true,
					checkThisOnly: false,
					checkUnionTypes: true,
					checkExplicitMixed: true,
					checkImplicitMixed: false,
					checkBenevolentUnionTypes: false,
					discoveringSymbolsTip: true,
				),
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/parameter-out-execution-end.php'], [
			[
				'Parameter &$p @param-out type of method ParameterOutExecutionEnd\Foo::foo2() expects string, string|null given.',
				21,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutExecutionEnd\Foo::foo2() expects string, string|null given.',
				23,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutExecutionEnd\Foo::foo3() expects string, string|null given.',
				34,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutExecutionEnd\Foo::foo4() expects string, string|null given.',
				47,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutExecutionEnd\Foo::foo6() expects int, string given.',
				69,
			],
			[
				'Parameter &$p @param-out type of function ParameterOutExecutionEnd\foo2() expects string, string|null given.',
				80,
			],
			[
				'Parameter &$p @param-out type of function ParameterOutExecutionEnd\foo2() expects string, string|null given.',
				82,
			],
		]);
	}

	public function testBug11363(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11363.php'], []);
	}

	public function testBug12330(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12330.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug15066(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15066.php'], [
			[
				'Parameter &$refs @param-out type of function Bug15066Variables\\variadicParamOutNeverWritten() expects string, string|null given.',
				35,
			],
		]);
	}

}
