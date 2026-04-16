<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

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
			new PhpVersion(PHP_VERSION_ID),
		);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-statement-result-discarded.php'], [
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\ClassWithStaticSideEffects::staticMethod() on a separate line discards return value.',
				19,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\ClassWithStaticSideEffects::differentCase() on a separate line discards return value.',
				27,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				41,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\ClassWithStaticSideEffects::staticMethod() on a separate line discards return value.',
				43,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				46,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\ClassWithStaticSideEffects::staticMethod() on a separate line discards return value.',
				48,
			],
			[
				'Call to static method StaticMethodCallStatementResultDiscarded\Foo::canDiscard() in (void) cast but method allows discarding return value.',
				51,
			],
		]);
	}

}
