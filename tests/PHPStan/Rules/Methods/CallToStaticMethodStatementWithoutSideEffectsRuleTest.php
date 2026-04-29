<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallToStaticMethodStatementWithoutSideEffectsRule>
 */
class CallToStaticMethodStatementWithoutSideEffectsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = self::createReflectionProvider();
		return new CallToStaticMethodStatementWithoutSideEffectsRule(
			new RuleLevelHelper(
				$broker,
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: false,
				checkImplicitMixed: false,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
			$broker,
		);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-statement-no-side-effects.php'], [
			[
				'Call to method DateTime::format() on a separate line has no effect.',
				23,
			],
		]);
	}

	#[RequiresPhp('< 8.0.0')]
	public function testRulePhp7(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-statement-no-side-effects.php'], [
			[
				'Call to static method DateTimeImmutable::createFromFormat() on a separate line has no effect.',
				12,
			],
			[
				'Call to method DateTime::format() on a separate line has no effect.',
				23,
			],
		]);
	}

	public function testPhpDoc(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-statement-no-side-effects-phpdoc.php'], [
			[
				'Call to static method StaticMethodCallStatementNoSideEffects\BzzStatic::pure1() on a separate line has no effect.',
				55,
			],
			[
				'Call to static method StaticMethodCallStatementNoSideEffects\BzzStatic::pure2() on a separate line has no effect.',
				56,
			],
			[
				'Call to static method StaticMethodCallStatementNoSideEffects\BzzStatic::pure3() on a separate line has no effect.',
				57,
			],
			[
				'Call to static method StaticMethodCallStatementNoSideEffects\BzzStatic::pure4() on a separate line has no effect.',
				58,
			],
			[
				'Call to static method StaticMethodCallStatementNoSideEffects\BzzStatic::pure5() on a separate line has no effect.',
				59,
			],
			[
				'Call to static method StaticMethodCallStatementNoSideEffects\PureThrows::pureAndThrowsVoid() on a separate line has no effect.',
				85,
			],
		]);
	}

	public function testBug4455(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4455-static.php'], []);
	}

	public function testBug12224(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12224.php'], []);
	}

	public function testFirstClassCallables(): void
	{
		$this->analyse([__DIR__ . '/data/first-class-callable-static-method-without-side-effect.php'], [
			[
				'Call to static method FirstClassCallableStaticMethodWithoutSideEffect\Foo::doFoo() on a separate line has no effect.',
				12,
			],
			[
				'Call to static method FirstClassCallableStaticMethodWithoutSideEffect\Bar::doFoo() on a separate line has no effect.',
				36,
			],
			[
				'Call to static method FirstClassCallableStaticMethodWithoutSideEffect\Bar::doBar() on a separate line has no effect.',
				39,
			],
		]);
	}

	public function testBug10819(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80000) {
			$errors = [
				[
					'Call to static method DateTime::createFromFormat() on a separate line has no effect.',
					13,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/bug-10819.php'], $errors);
	}

	public function testDynamicStaticCall(): void
	{
		$this->analyse([__DIR__ . '/data/dynamic-static-call.php'], [
			[
				'Call to static method DynamicStaticCall\Foo::doFoo() on a separate line has no effect.',
				32,
			],
			[
				'Call to static method DynamicStaticCall\FinalFoo::doFoo() on a separate line has no effect.',
				33,
			],
			[
				'Call to static method DynamicStaticCall\Bar::finalFoo() on a separate line has no effect.',
				34,
			],
		]);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testPipeOperator(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-without-side-effect-pipe.php'], [
			[
				'Call to static method StaticMethodCallWithoutSideEffectPipe\Foo::doPure() on a separate line has no effect.',
				33,
			],
		]);
	}

}
