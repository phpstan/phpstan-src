<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<StaticMethodCallableRule>
 */
class StaticMethodCallableRuleTest extends RuleTestCase
{

	private int $phpVersion = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false, true);

		$container = self::getContainer();
		return new StaticMethodCallableRule(
			new StaticMethodCallCheck(
				$reflectionProvider,
				$ruleLevelHelper,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				true,
				true,
				true,
			),
			new PhpVersion($this->phpVersion),
		);
	}

	#[RequiresPhp('< 8.1')]
	public function testNotSupportedOnOlderVersions(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-callable-not-supported.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-callable.php'], [
			[
				'Call to static method StaticMethodCallable\Foo::doFoo() with incorrect case: dofoo',
				11,
			],
			[
				'Call to static method doFoo() on an unknown class StaticMethodCallable\Nonexistent.',
				12,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Call to an undefined static method StaticMethodCallable\Foo::nonexistent().',
				13,
			],
			[
				'Static call to instance method StaticMethodCallable\Foo::doBar().',
				14,
			],
			[
				'Call to private static method doBar() of class StaticMethodCallable\Bar.',
				15,
			],
			[
				'Cannot call abstract static method StaticMethodCallable\Bar::doBaz().',
				16,
			],
			[
				'Call to static method doFoo() on an unknown class StaticMethodCallable\Nonexistent.',
				21,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Cannot call static method doFoo() on int.',
				22,
			],
			[
				'Creating callable from a non-native static method StaticMethodCallable\Lorem::doBar().',
				47,
			],
			[
				'Creating callable from a non-native static method StaticMethodCallable\Ipsum::doBar().',
				66,
			],
		]);
	}

	public function testBug8752(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-8752.php'], []);
	}

	public function testCallsOnGenericClassString(): void
	{
		$this->analyse([__DIR__ . '/../Comparison/data/impossible-method-exists-on-generic-class-string.php'], []);
	}

}
