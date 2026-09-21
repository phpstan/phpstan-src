<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<FunctionCallableRule>
 */
class FunctionCallableRuleTest extends RuleTestCase
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

		return new FunctionCallableRule(
			$reflectionProvider,
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
			true,
			true,
		);
	}

	#[RequiresPhp('< 8.1.0')]
	public function testNotSupportedOnOlderVersions(): void
	{
		$this->analyse([__DIR__ . '/data/function-callable-not-supported.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-callable.php'], [
			[
				'Function nonexistent not found.',
				13,
			],
			[
				'Creating callable from string but it might not be a callable.',
				19,
			],
			[
				'Creating callable from 1 but it\'s not a callable.',
				33,
			],
			[
				'Call to function strlen() with incorrect case: StrLen',
				38,
			],
			[
				'Creating callable from 1|(callable(): mixed) but it might not be a callable.',
				47,
			],
			[
				'Creating callable from an unknown class FunctionCallable\Nonexistent.',
				52,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testConditionallyExecutedCode(): void
	{
		self::$analysedPhpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/function-callable-php-versions.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				16,
			],
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				19,
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
