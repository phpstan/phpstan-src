<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Properties\PropertyDescriptor;
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
			new PropertyDescriptor(),
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
			]
		);
	}

	public function testBug5804(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5804.php'], [
			[
				'Property Bug5804\Blah::$value (array<int>|null) does not accept array<int, string>.',
				11,
			],
		]);
	}

	public function testKeys(): void
	{
		$this->analyse([__DIR__ . '/data/appended-array-key.php'], [
			[
				'Array (array<int, mixed>) does not accept key int|string.',
				28,
			],
			[
				'Array (array<int, mixed>) does not accept key string.',
				30,
			],
			[
				'Array (array<string, mixed>) does not accept key int.',
				31,
			],
			[
				'Array (array<string, mixed>) does not accept key int|string.',
				33,
			],
			[
				'Array (array<string, mixed>) does not accept key 0.',
				38,
			],
			[
				'Property AppendedArrayKey\Foo::$stringArray (array<string, mixed>) does not accept array{1: false}.',
				46,
			],
			[
				'Property AppendedArrayKey\MorePreciseKey::$test (array<1|2|3, string>) does not accept non-empty-array<int, string>.',
				80,
			],
			[
				'Property AppendedArrayKey\MorePreciseKey::$test (array<1|2|3, string>) does not accept non-empty-array<1|2|3|4, string>.',
				85,
			],
		]);
	}

	public function testBug5372Two(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5372_2.php'], []);
	}

	public function testBug5447(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5447.php'], []);
	}

}
