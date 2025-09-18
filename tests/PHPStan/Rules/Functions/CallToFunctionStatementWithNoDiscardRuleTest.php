<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CallToFunctionStatementWithNoDiscardRule>
 */
class CallToFunctionStatementWithNoDiscardRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToFunctionStatementWithNoDiscardRule(self::createReflectionProvider());
	}

	#[RequiresPhp('>= 8.1')]
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
			[
				'Call to callable \'FunctionCallStateme…\' on a separate line discards return value.',
				30,
			],
			[
				'Call to callable Closure(): array on a separate line discards return value.',
				35,
			],
			[
				'Call to callable Closure(): 1 on a separate line discards return value.',
				40,
			],
			[
				'Call to callable Closure(): 1 on a separate line discards return value.',
				45,
			],
		]);
	}

}
