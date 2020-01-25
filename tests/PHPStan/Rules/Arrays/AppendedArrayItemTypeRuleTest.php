<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<AppendedArrayItemTypeRule>
 */
class AppendedArrayItemTypeRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new AppendedArrayItemTypeRule(
			new PropertyReflectionFinder(),
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true)
		);
	}

	public function testAppendedArrayItemType(): void
	{
		$this->analyse(
			[__DIR__ . '/data/appended-array-item.php'],
			[
				[
					'Array (array<int>) does not accept string.',
					18,
				],
				[
					'Array (array<callable>) does not accept array<int, int>.',
					20,
				],
				[
					'Array (array<callable>) does not accept array<int, string>.',
					23,
				],
				[
					'Array (array<callable>) does not accept array<int, string>.',
					25,
				],
				[
					'Array (array<int>) does not accept string.',
					30,
				],
				[
					'Array (array<callable(): string>) does not accept Closure(): int.',
					43,
				],
				[
					'Array (array<AppendedArrayItem\Lorem>) does not accept AppendedArrayItem\Baz.',
					77,
				],
				[
					'ArrayAccess<int, int> does not accept string.',
					85,
				],
				[
					'ArrayAccess<int, int> does not accept string.',
					87,
				],
				[
					'ArrayAccess<int, int> does not accept string.',
					90,
				],
				[
					'ArrayAccess<int, int> does not accept array<int, string>.',
					91,
				],
			]
		);
	}

}
