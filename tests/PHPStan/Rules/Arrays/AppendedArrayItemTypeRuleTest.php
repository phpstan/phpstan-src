<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AppendedArrayItemTypeRule>
 */
class AppendedArrayItemTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AppendedArrayItemTypeRule(
			new PropertyReflectionFinder(),
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false),
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
					'Array (array<callable(): mixed>) does not accept array{1, 2, 3}.',
					20,
				],
				[
					'Array (array<callable(): mixed>) does not accept array{\'AppendedArrayItem\\\\Foo\', \'classMethod\'}.',
					23,
				],
				[
					'Array (array<callable(): mixed>) does not accept array{\'Foo\', \'Hello world\'}.',
					25,
				],
				[
					'Array (array<int>) does not accept string.',
					27,
				],
				[
					'Array (array<int>) does not accept string.',
					32,
				],
				[
					'Array (array<callable(): string>) does not accept Closure(): 1.',
					45,
				],
				[
					'Array (array<AppendedArrayItem\Lorem>) does not accept AppendedArrayItem\Baz.',
					79,
				],
				[
					'Array (array<array{opt?: int, req: int}>) does not accept array{req: 1, foo: 1}.',
					90,
					"Offset 'foo' is not accepted.",
				],
				[
					'Array (array<array{opt?: int, req: int}>) does not accept array{req: 1, foo: 1}|array{req: 1, opt: 1}.',
					91,
					"Offset 'foo' is not accepted.",
				],
			],
		);
	}

}
