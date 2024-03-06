<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ParameterOutExecutionEndTypeRule>
 */
class ParameterOutExecutionEndTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ParameterOutExecutionEndTypeRule(
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, true, false, true, false),
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
				'Parameter &$p @param-out type of method ParameterOutExecutionEnd\Foo::foo3() expects string, string|null given.',
				34,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutExecutionEnd\Foo::foo4() expects string, string|null given.',
				45,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutExecutionEnd\Foo::foo6() expects int, string given.',
				69,
			],
		]);
	}

}
