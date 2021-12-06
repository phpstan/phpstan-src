<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AppendedArrayKeyTypeRule>
 */
class AppendedArrayKeyTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AppendedArrayKeyTypeRule(
			new PropertyReflectionFinder(),
			true
		);
	}

	public function testRule(): void
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
				'Array (array<string, mixed>) does not accept key 1.',
				46,
			],
			[
				'Array (array<1|2|3, string>) does not accept key int.',
				80,
			],
			[
				'Array (array<1|2|3, string>) does not accept key 4.',
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
