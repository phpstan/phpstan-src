<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallToFunctionStatementWithNoDiscardRule>
 */
class CallToFunctionStatementWithNoDiscardRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToFunctionStatementWithNoDiscardRule(self::createReflectionProvider(), new PhpVersion(PHP_VERSION_ID));
	}

	#[RequiresPhp('>= 8.5.0')]
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
				'Call to callable Closure(int): array on a separate line discards return value.',
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
			[
				'Call to function FunctionCallStatementResultDiscarded\canDiscard() in (void) cast but function allows discarding return value.',
				55,
			],
			[
				'Call to callable \'FunctionCallStateme…\' in (void) cast but callable allows discarding return value.',
				59,
			],
			[
				'Call to function FunctionCallStatementResultDiscarded\withSideEffects() on a separate line discards return value.',
				61,
			],
			[
				'Call to function FunctionCallStatementResultDiscarded\canDiscard() in (void) cast but function allows discarding return value.',
				64,
			],
			[
				'Call to function FunctionCallStatementResultDiscarded\withSideEffects() on a separate line discards return value.',
				66,
			],
			[
				'Call to function FunctionCallStatementResultDiscarded\canDiscard() in (void) cast but function allows discarding return value.',
				69,
			],
		]);
	}

}
