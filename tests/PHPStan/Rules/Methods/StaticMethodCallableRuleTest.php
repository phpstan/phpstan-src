<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use Override;
use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<StaticMethodCallableRule>
 */
class StaticMethodCallableRuleTest extends RuleTestCase
{

	private static ?int $analysedPhpVersionId = null;

	#[Override]
	protected function setUp(): void
	{
		self::$analysedPhpVersionId = null;
		parent::setUp();
	}

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper(
			$reflectionProvider,
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: false,
			checkImplicitMixed: false,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);

		$container = self::getContainer();
		return new StaticMethodCallableRule(
			new StaticMethodCallCheck(
				$reflectionProvider,
				$ruleLevelHelper,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck(
						$reflectionProvider,
						checkInternalClassCaseSensitivity: true,
						checkImportedClassNameCase: true,
					),
					new ClassForbiddenNameCheck($container->getExtensionsCollection(ForbiddenClassNameExtension::class)),
					$reflectionProvider,
					$container->getExtensionsCollection(RestrictedClassNameUsageExtension::class),
				),
				checkFunctionNameCase: true,
				discoveringSymbolsTip: true,
				reportMagicMethods: true,
			),
		);
	}

	#[RequiresPhp('< 8.1.0')]
	public function testNotSupportedOnOlderVersions(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-callable-not-supported.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
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

	public function testConditionallyExecutedCode(): void
	{
		self::$analysedPhpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/static-method-callable-php-versions.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				19,
			],
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				22,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		if (self::$analysedPhpVersionId === null) {
			return [];
		}

		return [__DIR__ . '/../php-version-' . self::$analysedPhpVersionId . '.neon'];
	}

}
