<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

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

	// #[\NoDiscard] is an attribute, a comment on PHP 7.4
	#[RequiresPhp('>= 8.0.0')]
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
				27,
			],
		]);
	}

	// the (void) cast and the pipe operator are PHP 8.5 syntax
	#[RequiresPhp('>= 8.5.0')]
	public function testRulePhp85(): void
	{
		$this->analyse([__DIR__ . '/data/method-call-statement-result-discarded-php85.php'], [
			[
				'Call to method MethodCallStatementResultDiscardedPhp85\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				27,
			],
			[
				'Call to method MethodCallStatementResultDiscardedPhp85\ClassWithInstanceSideEffects::instanceMethod() on a separate line discards return value.',
				29,
			],
			[
				'Call to method MethodCallStatementResultDiscardedPhp85\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				32,
			],
			[
				'Call to method MethodCallStatementResultDiscardedPhp85\ClassWithInstanceSideEffects::instanceMethod() on a separate line discards return value.',
				34,
			],
			[
				'Call to method MethodCallStatementResultDiscardedPhp85\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				37,
			],
		]);
	}

}
