<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Methods\AlwaysUsedMethodExtension;
use PHPStan\Rules\Methods\DirectAlwaysUsedMethodExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnusedPrivateMethodRule>
 */
class UnusedPrivateMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedPrivateMethodRule(
			new DirectAlwaysUsedMethodExtensionProvider([
				new class() implements AlwaysUsedMethodExtension {

					public function isAlwaysUsed(MethodReflection $methodReflection): bool
					{
						return $methodReflection->getDeclaringClass()->is('UnusedPrivateMethod\IgnoredByExtension')
							&& $methodReflection->getName() === 'foo';
					}

				},
			]),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-private-method.php'], [
			[
				'Method UnusedPrivateMethod\Foo::doFoo() is unused.',
				8,
			],
			[
				'Method UnusedPrivateMethod\Foo::doBar() is unused.',
				13,
			],
			[
				'Static method UnusedPrivateMethod\Foo::unusedStaticMethod() is unused.',
				44,
			],
			[
				'Method UnusedPrivateMethod\Bar::doBaz() is unused.',
				59,
			],
			[
				'Method UnusedPrivateMethod\Lorem::doBaz() is unused.',
				99,
			],
			[
				'Method UnusedPrivateMethod\IgnoredByExtension::bar() is unused.',
				181,
			],
		]);
	}

	public function testBug3630(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3630.php'], []);
	}

	public function testNullsafe(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-unused-private-method.php'], []);
	}

	public function testFirstClassCallable(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/callable-unused-private-method.php'], []);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/unused-private-method-enum.php'], [
			[
				'Method UnusedPrivateMethodEnunm\Foo::doBaz() is unused.',
				18,
			],
		]);
	}

	public function testBug7389(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7389.php'], [
			[
				'Method Bug7389\HelloWorld::getTest() is unused.',
				11,
			],
			[
				'Method Bug7389\HelloWorld::getTest1() is unused.',
				23,
			],
		]);
	}

	public function testBug8346(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8346.php'], []);
	}

	public function testFalsePositiveWithTraitUse(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/unused-method-false-positive-with-trait.php'], []);
	}

	public function testBug6039(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6039.php'], []);
	}

	public function testBug9765(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9765.php'], []);
	}

}
