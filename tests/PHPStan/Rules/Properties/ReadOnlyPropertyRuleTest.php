<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadOnlyPropertyRule>
 */
class ReadOnlyPropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyRule();
	}

	/**
	 * @return iterable<array{list<array{0: string, 1: int, 2?: string}>}>
	 */
	public static function dataRule(): iterable
	{
		if (PHP_VERSION_ID < 80100) {
			yield [
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
			];

			return;
		}

		yield [
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
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataRule')]
	public function testRule(array $errors): void
	{
		$this->analyse([__DIR__ . '/data/read-only-property.php'], $errors);
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataRule')]
	public function testRuleReadonlyClass(array $errors): void
	{
		$this->analyse([__DIR__ . '/data/read-only-property-readonly-class.php'], $errors);
	}

	public function testConditionallyDeclaredClass(): void
	{
		$errors = [
			[
				'Readonly properties are supported only on PHP 8.1 and later.',
				18,
			],
		];
		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'Readonly properties are supported only on PHP 8.1 and later.',
				26,
			];
		}

		$this->analyse([__DIR__ . '/data/read-only-property-php-versions.php'], $errors);
	}

}
