<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToStaticMethodStatementWithoutSideEffectsRule>
 */
class CallToStaticMethodStatementWithoutSideEffectsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new CallToStaticMethodStatementWithoutSideEffectsRule(
			new RuleLevelHelper($broker, true, false, true, false, false, true, false),
			$broker,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-statement-no-side-effects.php'], [
			[
				'Call to static method DateTimeImmutable::createFromFormat() on a separate line has no effect.',
				12,
			],
			[
				'Call to static method DateTimeImmutable::createFromFormat() on a separate line has no effect.',
				13,
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

}
