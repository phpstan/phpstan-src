<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MethodCallableRule>
 */
class MethodCallableRuleTest extends RuleTestCase
{

	private int $phpVersion = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false, true);

		return new MethodCallableRule(
			new MethodCallCheck($reflectionProvider, $ruleLevelHelper, true, true),
			new PhpVersion($this->phpVersion),
		);
	}

	public function testNotSupportedOnOlderVersions(): void
	{
		if (PHP_VERSION_ID >= 80100) {
			self::markTestSkipped('Test runs on PHP < 8.1.');
		}
		$this->analyse([__DIR__ . '/data/method-callable-not-supported.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				10,
			],
		]);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			self::markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/method-callable.php'], [
			[
				'Call to method MethodCallable\Foo::doFoo() with incorrect case: dofoo',
				11,
			],
			[
				'Call to an undefined method MethodCallable\Foo::doNonexistent().',
				12,
			],
			[
				'Cannot call method doFoo() on int.',
				13,
			],
			[
				'Call to private method doBar() of class MethodCallable\Bar.',
				18,
			],
			[
				'Call to method doFoo() on an unknown class MethodCallable\Nonexistent.',
				23,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Call to private method doFoo() of class MethodCallable\ParentClass.',
				53,
			],
			[
				'Creating callable from a non-native method MethodCallable\Lorem::doBar().',
				66,
			],
			[
				'Creating callable from a non-native method MethodCallable\Ipsum::doBar().',
				85,
			],
		]);
	}

}
