<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToFunctionStatementWithNoDiscardRule>
 */
class CallToFunctionStatementWithNoDiscardRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToFunctionStatementWithNoDiscardRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-call-statement-result-discarded.php'], [
			[
				'Call to function FunctionCallStatementResultDiscarded\withSideEffects() on a separate line discards return value.',
				11,
			],
			[
				'Call to function FunctionCallStatementResultDiscarded\differentCase() on a separate line discards return value.',
				25,
			],
		]);
	}

}
