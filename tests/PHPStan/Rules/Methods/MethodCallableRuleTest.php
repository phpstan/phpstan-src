<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MethodCallableRule>
 */
class MethodCallableRuleTest extends RuleTestCase
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

		return new MethodCallableRule(
			new MethodCallCheck(
				$reflectionProvider,
				$ruleLevelHelper,
				checkFunctionNameCase: true,
				reportMagicMethods: true,
			),
		);
	}

	#[RequiresPhp('< 8.1.0')]
	public function testNotSupportedOnOlderVersions(): void
	{
		$this->analyse([__DIR__ . '/data/method-callable-not-supported.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug13596(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13596.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
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
		]);
	}

	public function testConditionallyExecutedCode(): void
	{
		self::$analysedPhpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/method-callable-php-versions.php'], [
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
