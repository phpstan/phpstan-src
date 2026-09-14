<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToMethodStatementWithNoDiscardRule>
 */
class CallToMethodStatementWithNoDiscardRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToMethodStatementWithNoDiscardRule(
			new RuleLevelHelper(
				self::createReflectionProvider(),
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: false,
				checkImplicitMixed: false,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-call-statement-result-discarded.php'], [
			[
				'Call to method MethodCallStatementResultDiscarded\ClassWithInstanceSideEffects::instanceMethod() on a separate line discards return value.',
				20,
			],
			[
				'Call to method MethodCallStatementResultDiscarded\ClassWithInstanceSideEffects::instanceMethod() on a separate line discards return value.',
				21,
			],
			[
				'Call to method MethodCallStatementResultDiscarded\ClassWithInstanceSideEffects::differentCase() on a separate line discards return value.',
				30,
			],
			[
				'Call to method MethodCallStatementResultDiscarded\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				45,
			],
			[
				'Call to method MethodCallStatementResultDiscarded\ClassWithInstanceSideEffects::instanceMethod() on a separate line discards return value.',
				47,
			],
			[
				'Call to method MethodCallStatementResultDiscarded\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				50,
			],
			[
				'Call to method MethodCallStatementResultDiscarded\ClassWithInstanceSideEffects::instanceMethod() on a separate line discards return value.',
				52,
			],
			[
				'Call to method MethodCallStatementResultDiscarded\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				55,
			],
		]);
	}

}
