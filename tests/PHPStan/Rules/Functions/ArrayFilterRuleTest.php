<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ArrayFilterRule>
 */
class ArrayFilterRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		return new ArrayFilterRule($this->treatPhpDocTypesAsCertain);
	}

	public function testFile(): void
	{
		$expectedErrors = [
			[
				'Parameter #1 $array (array{1, 3}) to function array_filter does not contain falsy values, the array will always stay the same.',
				11,
			],
			[
				'Parameter #1 $array (array{\'test\'}) to function array_filter does not contain falsy values, the array will always stay the same.',
				12,
			],
			[
				'Parameter #1 $array (array{true, true}) to function array_filter does not contain falsy values, the array will always stay the same.',
				17,
			],
			[
				'Parameter #1 $array (array{stdClass}) to function array_filter does not contain falsy values, the array will always stay the same.',
				18,
			],
			[
				'Parameter #1 $array (array<stdClass>) to function array_filter does not contain falsy values, the array will always stay the same.',
				20,
			],
			[
				'Parameter #1 $array (array{0}) to function array_filter contains falsy values only, the result will always be an empty array.',
				23,
			],
			[
				'Parameter #1 $array (array{null}) to function array_filter contains falsy values only, the result will always be an empty array.',
				24,
			],
			[
				'Parameter #1 $array (array{null, null}) to function array_filter contains falsy values only, the result will always be an empty array.',
				25,
			],
			[
				'Parameter #1 $array (array{null, 0}) to function array_filter contains falsy values only, the result will always be an empty array.',
				26,
			],
			[
				'Parameter #1 $array (array<false|null>) to function array_filter contains falsy values only, the result will always be an empty array.',
				27,
			],
			[
				'Parameter #1 $array (array{}) to function array_filter is empty, call has no effect.',
				28,
			],
		];

		$this->analyse([__DIR__ . '/data/array_filter_empty.php'], $expectedErrors);
	}

	public function testBug2065WithPhpDocTypesAsCertain(): void
	{
		$expectedErrors = [
			[
				'Parameter #1 $array (array<class-string>) to function array_filter does not contain falsy values, the array will always stay the same.',
				12,
			],
		];

		$this->analyse([__DIR__ . '/data/bug-array-filter.php'], $expectedErrors);
	}

	public function testBug2065WithoutPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = false;

		$this->analyse([__DIR__ . '/data/bug-array-filter.php'], []);
	}

}
