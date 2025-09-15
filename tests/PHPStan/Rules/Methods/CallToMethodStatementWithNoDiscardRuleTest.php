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
		return new CallToMethodStatementWithNoDiscardRule(new RuleLevelHelper(self::createReflectionProvider(), true, false, true, false, false, false, true));
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
		]);
	}

}
