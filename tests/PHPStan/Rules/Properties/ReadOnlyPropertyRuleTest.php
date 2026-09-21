<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @extends RuleTestCase<ReadOnlyPropertyRule>
 */
class ReadOnlyPropertyRuleTest extends RuleTestCase
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
		return new ReadOnlyPropertyRule();
	}

	public static function dataRule(): array
	{
		return [
			[
				80000,
				[
					[
						'Readonly properties are supported only on PHP 8.1 and later.',
						8,
					],
					[
						'Readonly properties are supported only on PHP 8.1 and later.',
						9,
					],
					[
						'Readonly property must have a native type.',
						9,
					],
					[
						'Readonly properties are supported only on PHP 8.1 and later.',
						10,
					],
					[
						'Readonly property cannot have a default value.',
						10,
					],
					[
						'Readonly properties are supported only on PHP 8.1 and later.',
						16,
					],
					[
						'Readonly properties are supported only on PHP 8.1 and later.',
						23,
					],
					[
						'Readonly property cannot be static.',
						23,
					],
				],
			],
			[
				80100,
				[
					[
						'Readonly property must have a native type.',
						9,
					],
					[
						'Readonly property cannot have a default value.',
						10,
					],
					[
						'Readonly property cannot be static.',
						23,
					],
				],
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
		$this->analyse([__DIR__ . '/data/read-only-property.php'], $errors);
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataRule')]
	public function testRuleReadonlyClass(int $phpVersionId, array $errors): void
	{
		self::$analysedPhpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/read-only-property-readonly-class.php'], $errors);
	}

	public function testConditionallyDeclaredClass(): void
	{
		self::$analysedPhpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/read-only-property-php-versions.php'], [
			[
				'Readonly properties are supported only on PHP 8.1 and later.',
				18,
			],
			[
				'Readonly properties are supported only on PHP 8.1 and later.',
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
