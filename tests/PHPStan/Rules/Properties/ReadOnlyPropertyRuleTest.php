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

	/** @var int */
	private $phpVersionId;

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyRule(new PhpVersion($this->phpVersionId));
	}

	public function dataRule(): array
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
				],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 * @param mixed[] $errors
	 */
	public function testRule(int $phpVersionId, array $errors): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/read-only-property.php'], $errors);
	}

}
