<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @extends RuleTestCase<ThrowExpressionRule>
 */
class ThrowExpressionRuleTest extends RuleTestCase
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
		return new ThrowExpressionRule();
	}

	public static function dataRule(): array
	{
		return [
			[
				70400,
				[
					[
						'Throw expression is supported only on PHP 8.0 and later.',
						10,
					],
				],
			],
			[
				80000,
				[],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[DataProvider('dataRule')]
	public function testRule(int $phpVersion, array $expectedErrors): void
	{
		self::$analysedPhpVersionId = $phpVersion;
		$this->analyse([__DIR__ . '/data/throw-expr.php'], $expectedErrors);
	}

	public function testConditionallyExecutedCode(): void
	{
		self::$analysedPhpVersionId = 70400;
		$this->analyse([__DIR__ . '/data/throw-expr-php-versions.php'], [
			[
				'Throw expression is supported only on PHP 8.0 and later.',
				15,
			],
			[
				'Throw expression is supported only on PHP 8.0 and later.',
				18,
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
