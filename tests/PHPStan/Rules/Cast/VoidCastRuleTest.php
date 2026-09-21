<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<VoidCastRule>
 */
class VoidCastRuleTest extends RuleTestCase
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
		return new VoidCastRule();
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testPrintRule(): void
	{
		$this->analyse([__DIR__ . '/data/void-cast.php'], [
			[
				'The (void) cast cannot be used within an expression.',
				5,
			],
			[
				'The (void) cast cannot be used within an expression.',
				6,
			],
			[
				'The (void) cast cannot be used within an expression.',
				7,
			],
		]);
	}

	public function testSupport(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80500) {
			$errors = [
				[
					'The (void) cast is supported only on PHP 8.5 and later.',
					10,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/void-cast-support.php'], $errors);
	}

	public function testConditionallyExecutedCode(): void
	{
		self::$analysedPhpVersionId = 80400;
		$this->analyse([__DIR__ . '/data/void-cast-php-versions.php'], [
			[
				'The (void) cast is supported only on PHP 8.5 and later.',
				12,
			],
			[
				'The (void) cast is supported only on PHP 8.5 and later.',
				15,
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
