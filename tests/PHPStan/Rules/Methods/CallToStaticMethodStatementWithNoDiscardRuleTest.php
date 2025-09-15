<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToStaticMethodStatementWithNoDiscardRule>
 */
class CallToStaticMethodStatementWithNoDiscardRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new CallToStaticMethodStatementWithNoDiscardRule(
			new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false, true),
			$reflectionProvider,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-statement-result-discarded.php'], [
			[
				'Call to static method MethodCallStatementResultDiscarded\ClassWithStaticSideEffects::staticMethod() on a separate line discards return value.',
				19,
			],
			[
				'Call to static method MethodCallStatementResultDiscarded\ClassWithStaticSideEffects::differentCase() on a separate line discards return value.',
				27,
			],
		]);
	}

}
