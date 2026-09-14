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

	// #[\NoDiscard] is an attribute, a comment on PHP 7.4
	#[RequiresPhp('>= 8.0.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-call-statement-result-discarded.php'], [
			[
				'Call to function FunctionCallStatementResultDiscarded\withSideEffects() on a separate line discards return value.',
				11,
			],
			[
				'Call to function FunctionCallStatementResultDiscarded\differentCase() on a separate line discards return value.',
				23,
			],
			[
				'Call to callable \'FunctionCallStateme…\' on a separate line discards return value.',
				28,
			],
			[
				'Call to callable Closure(int): array on a separate line discards return value.',
				33,
			],
			[
				'Call to callable Closure(): 1 on a separate line discards return value.',
				38,
			],
			[
				'Call to callable Closure(): 1 on a separate line discards return value.',
				43,
			],
		]);
	}

	// the (void) cast and the pipe operator are PHP 8.5 syntax
	#[RequiresPhp('>= 8.5.0')]
	public function testRulePhp85(): void
	{
		$this->analyse([__DIR__ . '/data/function-call-statement-result-discarded-php85.php'], [
			[
				'Call to function FunctionCallStatementResultDiscardedPhp85\canDiscard() in (void) cast but function allows discarding return value.',
				17,
			],
			[
				'Call to callable \'FunctionCallStateme…\' in (void) cast but callable allows discarding return value.',
				20,
			],
			[
				'Call to function FunctionCallStatementResultDiscardedPhp85\withSideEffects() on a separate line discards return value.',
				22,
			],
			[
				'Call to function FunctionCallStatementResultDiscardedPhp85\canDiscard() in (void) cast but function allows discarding return value.',
				25,
			],
			[
				'Call to function FunctionCallStatementResultDiscardedPhp85\withSideEffects() on a separate line discards return value.',
				27,
			],
			[
				'Call to function FunctionCallStatementResultDiscardedPhp85\canDiscard() in (void) cast but function allows discarding return value.',
				30,
			],
		]);
	}

}
