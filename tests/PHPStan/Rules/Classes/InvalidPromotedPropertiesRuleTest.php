<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidPromotedPropertiesRule>
 */
class InvalidPromotedPropertiesRuleTest extends RuleTestCase
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
		return new InvalidPromotedPropertiesRule();
	}

	public function testNotSupportedOnPhp7(): void
	{
		self::$analysedPhpVersionId = 70400;
		$this->analyse([__DIR__ . '/data/invalid-promoted-properties.php'], [
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				8,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				10,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				17,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				21,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				23,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				31,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				38,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				45,
			],
		]);
	}

	public function testSupportedOnPhp8(): void
	{
		self::$analysedPhpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/invalid-promoted-properties.php'], [
			[
				'Promoted properties can be in constructor only.',
				10,
			],
			[
				'Promoted properties can be in constructor only.',
				17,
			],
			[
				'Promoted properties can be in constructor only.',
				21,
			],
			[
				'Promoted properties can be in constructor only.',
				23,
			],
			[
				'Promoted properties are not allowed in abstract constructors.',
				31,
			],
			[
				'Promoted properties are not allowed in abstract constructors.',
				38,
			],
			[
				'Promoted property parameter $i can not be variadic.',
				45,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9577(): void
	{
		self::$analysedPhpVersionId = 80100;
		$this->analyse([__DIR__ . '/data/bug-9577.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testHooks(): void
	{
		self::$analysedPhpVersionId = 80100;
		$this->analyse([__DIR__ . '/data/invalid-hooked-properties.php'], [
			[
				'Promoted properties can be in constructor only.',
				9,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testFinalProperty(): void
	{
		self::$analysedPhpVersionId = null;
		$errors = [];
		if (PHP_VERSION_ID < 80500) {
			$errors = [
				[
					'Final promoted properties are supported only on PHP 8.5 and later.',
					8,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/final-promoted-property.php'], $errors);
	}

	public function testConditionallyDeclaredClass(): void
	{
		self::$analysedPhpVersionId = 70400;
		$this->analyse([__DIR__ . '/data/promoted-properties-php-versions.php'], [
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				20,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				30,
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
