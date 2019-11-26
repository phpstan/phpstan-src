<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

/**
 * @extends \PHPStan\Testing\RuleTestCase<AppendedArrayKeyTypeRule>
 */
class AppendedArrayKeyTypeRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new AppendedArrayKeyTypeRule(true);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/appended-array-key.php'], [
			[
				'Array (array<int, mixed>) does not accept key int|string.',
				31,
			],
			[
				'Array (array<int, mixed>) does not accept key string.',
				37,
			],
			[
				'Array (array<string, mixed>) does not accept key int.',
				40,
			],
			[
				'Array (array<string, mixed>) does not accept key int|string.',
				46,
			],
			[
				'Array (array<string, mixed>) does not accept key 0.',
				61,
			],
			[
				'Array (array) does not accept key 1.',
				70,
			],
		]);
	}

}
