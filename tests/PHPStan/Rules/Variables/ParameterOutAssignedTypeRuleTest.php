<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ParameterOutAssignedTypeRule>
 */
class ParameterOutAssignedTypeRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ParameterOutAssignedTypeRule(
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, true, false, true, false),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/parameter-out-assigned-type.php'], [
			[
				'Parameter &$p out type of function ParameterOutAssignedType\foo() expects int, string given.',
				10,
			],
			[
				'Parameter &$p out type of method ParameterOutAssignedType\Foo::doFoo() expects int, string given.',
				21,
			],
			[
				'Parameter &$p out type of method ParameterOutAssignedType\Foo::doBar() expects string, int given.',
				29,
			],
			[
				'Parameter &$p out type of method ParameterOutAssignedType\Foo::doBaz() expects list<int>, array<0|int<2, max>, int> given.',
				38,
			],
			[
				'Parameter &$p out type of method ParameterOutAssignedType\Foo::doBaz2() expects list<int>, non-empty-list<\'str\'|int> given.',
				47,
			],
			[
				'Parameter &$p out type of method ParameterOutAssignedType\Foo::doBaz3() expects list<list<int>>, array<int<0, max>, array<int<0, max>, int>> given.',
				56,
			],
		]);
	}

}
