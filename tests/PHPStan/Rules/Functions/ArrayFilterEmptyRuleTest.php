<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ArrayFilterEmptyRule>
 */
class ArrayFilterEmptyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ArrayFilterEmptyRule($this->createReflectionProvider());
	}

	public function testFile(): void
	{
		$expectedErrors = [
			[
				'Parameter #1 $array (array{1, 3}) to function array_filter cannot contain empty values, call has no effect.',
				11,
			],
			[
				'Parameter #1 $array (array{\'test\'}) to function array_filter cannot contain empty values, call has no effect.',
				12,
			],
			[
				'Parameter #1 $array (array{true, true}) to function array_filter cannot contain empty values, call has no effect.',
				17,
			],
			[
				'Parameter #1 $array (array{stdClass}) to function array_filter cannot contain empty values, call has no effect.',
				18,
			],
			[
				'Parameter #1 $array (array<stdClass>) to function array_filter cannot contain empty values, call has no effect.',
				20,
			],
			[
				'Parameter #1 $array (array{0}) to function array_filter can only contain empty values, call always results in [].',
				23,
			],
			[
				'Parameter #1 $array (array{null}) to function array_filter can only contain empty values, call always results in [].',
				24,
			],
			[
				'Parameter #1 $array (array{null, null}) to function array_filter can only contain empty values, call always results in [].',
				25,
			],
			[
				'Parameter #1 $array (array{null, 0}) to function array_filter can only contain empty values, call always results in [].',
				26,
			],
			[
				'Parameter #1 $array (array<false|null>) to function array_filter can only contain empty values, call always results in [].',
				27,
			],
			[
				'Parameter #1 $array (array{}) to function array_filter is empty, call has no effect.',
				28,
			],
		];

		$this->analyse([__DIR__ . '/data/array_filter_empty.php'], $expectedErrors);
	}

}
