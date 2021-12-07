<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallToMethodStatementWithoutSideEffectsRule>
 */
class CallToMethodStatementWithoutSideEffectsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToMethodStatementWithoutSideEffectsRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-call-statement-no-side-effects.php'], [
			[
				'Call to method DateTimeImmutable::modify() on a separate line has no effect.',
				15,
			],
			[
				'Call to static method DateTimeImmutable::createFromFormat() on a separate line has no effect.',
				16,
			],
			[
				'Call to method Exception::getCode() on a separate line has no effect.',
				21,
			],
			[
				'Call to method MethodCallStatementNoSideEffects\Bar::doPure() on a separate line has no effect.',
				63,
			],
			[
				'Call to method MethodCallStatementNoSideEffects\Bar::doPureWithThrowsVoid() on a separate line has no effect.',
				64,
			],
		]);
	}

	public function testNullsafe(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/nullsafe-method-call-statement-no-side-effects.php'], [
			[
				'Call to method Exception::getMessage() on a separate line has no effect.',
				10,
			],
		]);
	}

	public function testBug4232(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4232.php'], []);
	}

	public function testPhpDoc(): void
	{
		$this->analyse([__DIR__ . '/data/method-call-statement-no-side-effects-phpdoc.php'], [
			[
				'Call to method MethodCallStatementNoSideEffects\Bzz::pure1() on a separate line has no effect.',
				39,
			],
			[
				'Call to method MethodCallStatementNoSideEffects\Bzz::pure2() on a separate line has no effect.',
				40,
			],
			[
				'Call to method MethodCallStatementNoSideEffects\Bzz::pure3() on a separate line has no effect.',
				41,
			],
		]);
	}

	public function testBug4455(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4455.php'], []);
	}

	public function testFirstClassCallables(): void
	{
		if (PHP_VERSION_ID < 80100) {
			self::markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/first-class-callable-method-without-side-effect.php'], [
			[
				'Call to method FirstClassCallableMethodWithoutSideEffect\Foo::doFoo() on a separate line has no effect.',
				12,
			],
			[
				'Call to method FirstClassCallableMethodWithoutSideEffect\Bar::doFoo() on a separate line has no effect.',
				36,
			],
			[
				'Call to method FirstClassCallableMethodWithoutSideEffect\Bar::doBar() on a separate line has no effect.',
				39,
			],
		]);
	}

}
