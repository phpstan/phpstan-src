<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @extends RuleTestCase<FinalConstantRule>
 */
class FinalConstantRuleTest extends RuleTestCase
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
		return new FinalConstantRule();
	}

	public static function dataRule(): array
	{
		return [
			[
				80000,
				[
					[
						'Final class constants are supported only on PHP 8.1 and later.',
						9,
					],
				],
			],
			[
				80100,
				[],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataRule')]
	public function testRule(int $phpVersionId, array $errors): void
	{
		self::$analysedPhpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/final-constant.php'], $errors);
	}

	public function testConditionallyDeclaredClass(): void
	{
		self::$analysedPhpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/final-constant-php-versions.php'], [
			[
				'Final class constants are supported only on PHP 8.1 and later.',
				18,
			],
			[
				'Final class constants are supported only on PHP 8.1 and later.',
				26,
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
