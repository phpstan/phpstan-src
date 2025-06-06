<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReadOnlyPropertyRule>
 */
class ReadOnlyPropertyRuleTest extends RuleTestCase
{

	private int $phpVersionId;

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyRule(new PhpVersion($this->phpVersionId));
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
	 * @dataProvider dataRule
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testRule(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/read-only-property.php'], $errors);
	}

	/**
	 * @dataProvider dataRule
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testRuleReadonlyClass(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/read-only-property-readonly-class.php'], $errors);
	}

}
