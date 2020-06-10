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
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false)
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
					'Array (array<callable(): mixed>) does not accept array(1, 2, 3).',
					20,
				],
				[
					'Array (array<callable(): mixed>) does not accept array(\'Closure\', \'bind\').',
					21,
				],
				[
					'Array (array<callable(): mixed>) does not accept array(\'AppendedArrayItem\\\\Foo\', \'classMethod\').',
					24,
				],
				[
					'Array (array<callable(): mixed>) does not accept array(\'Foo\', \'Hello world\').',
					26,
				],
				[
					'Array (array<int>) does not accept string.',
					31,
				],
				[
					'Array (array<callable(): string>) does not accept Closure(): int.',
					44,
				],
				[
					'Array (array<AppendedArrayItem\Lorem>) does not accept AppendedArrayItem\Baz.',
					78,
				],
			]
		);
	}

}
