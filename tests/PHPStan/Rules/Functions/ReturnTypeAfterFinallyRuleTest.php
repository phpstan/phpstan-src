<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ReturnTypeAfterFinallyRule>
 */
class ReturnTypeAfterFinallyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReturnTypeAfterFinallyRule(
			new RuleLevelHelper(
				self::createReflectionProvider(),
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: true,
				checkImplicitMixed: false,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/return-type-after-finally.php'], [
			[
				'Function ReturnTypeAfterFinally\\byRefChangedInFinally() should return int but returns string because the finally block modifies the value returned by reference.',
				9,
			],
			[
				'Function ReturnTypeAfterFinally\\byRefIncrementedInFinally() should return int but returns string because the finally block modifies the value returned by reference.',
				49,
			],
			[
				'Function ReturnTypeAfterFinally\\byRefStaticVariable() should return int but returns string because the finally block modifies the value returned by reference.',
				59,
			],
			[
				'Function ReturnTypeAfterFinally\\byRefPhpDocReturnType() should return int<1, max> but returns -5 because the finally block modifies the value returned by reference.',
				70,
			],
			[
				'Function ReturnTypeAfterFinally\\byRefReturnFromCatch() should return int but returns string because the finally block modifies the value returned by reference.',
				82,
			],
			[
				'Function ReturnTypeAfterFinally\\byRefNestedFinallyBothBroken() should return int but returns string because the finally block modifies the value returned by reference.',
				93,
			],
			[
				'Function ReturnTypeAfterFinally\\byRefNestedOuterFinallyUnrelated() should return int but returns string because the finally block modifies the value returned by reference.',
				121,
			],
			[
				'Method ReturnTypeAfterFinally\\Foo::byRefMethod() should return int but returns string because the finally block modifies the value returned by reference.',
				163,
			],
			[
				'Method ReturnTypeAfterFinally\\Foo::byRefStaticMethod() should return int but returns string because the finally block modifies the value returned by reference.',
				173,
			],
			[
				'Method ReturnTypeAfterFinally\\Foo::byRefProperty() should return int but returns string because the finally block modifies the value returned by reference.',
				185,
			],
			[
				'Anonymous function should return 0 but returns \'test\' because the finally block modifies the value returned by reference.',
				207,
			],
			[
				'Function ReturnTypeAfterFinally\\byRefArrayOffset() should return int but returns string because the finally block modifies the value returned by reference.',
				239,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPropertyHooks(): void
	{
		$this->analyse([__DIR__ . '/data/return-type-after-finally-property-hooks.php'], [
			[
				'Get hook for property ReturnTypeAfterFinallyPropertyHooks\\Foo::$byRefHook should return int but returns string because the finally block modifies the value returned by reference.',
				17,
			],
		]);
	}

	public function testBug13338(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13338.php'], [
			[
				'Function Bug13338\\test() should return int but returns string because the finally block modifies the value returned by reference.',
				8,
			],
		]);
	}

}
