<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CallToStaticMethodStatementWithNoDiscardRule>
 */
class CallToStaticMethodStatementWithNoDiscardRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new CallToStaticMethodStatementWithNoDiscardRule(
			new RuleLevelHelper(
				$reflectionProvider,
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: false,
				checkImplicitMixed: false,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
			$reflectionProvider,
		);
	}

	// #[\NoDiscard] is an attribute, a comment on PHP 7.4
	#[RequiresPhp('>= 8.0.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-statement-result-discarded.php'], [
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\ClassWithStaticSideEffects::staticMethod() on a separate line discards return value.',
				19,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\ClassWithStaticSideEffects::differentCase() on a separate line discards return value.',
				25,
			],
		]);
	}

	// the (void) cast and the pipe operator are PHP 8.5 syntax
	#[RequiresPhp('>= 8.5.0')]
	public function testRulePhp85(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-statement-result-discarded-php85.php'], [
			[
				'Call to static method StaticMethodCallStatementResultDiscardedPhp85\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				23,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscardedPhp85\ClassWithStaticSideEffects::staticMethod() on a separate line discards return value.',
				25,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscardedPhp85\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				28,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscardedPhp85\ClassWithStaticSideEffects::staticMethod() on a separate line discards return value.',
				30,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscardedPhp85\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				33,
			],
		]);
	}

}
