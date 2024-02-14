<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ArrayValuesRule>
 */
class ArrayValuesRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		return new ArrayValuesRule($this->createReflectionProvider(), $this->treatPhpDocTypesAsCertain);
	}

	public function testFile(): void
	{
		$expectedErrors = [
			[
				'Parameter #1 $array (array{0, 1, 3}) of array_values is already a list, call has no effect.',
				8,
			],
			[
				'Parameter #1 $array (array{1, 3}) of array_values is already a list, call has no effect.',
				9,
			],
			[
				'Parameter #1 $array (array{\'test\'}) of array_values is already a list, call has no effect.',
				10,
			],
			[
				'Parameter #1 $array (array{\'\', \'test\'}) of array_values is already a list, call has no effect.',
				12,
			],
			[
				'Parameter #1 $array (list<int>) of array_values is already a list, call has no effect.',
				14,
			],
			[
				'Parameter #1 $array (array{0}) of array_values is already a list, call has no effect.',
				17,
			],
			[
				'Parameter #1 $array (array{null, null}) of array_values is already a list, call has no effect.',
				19,
			],
			[
				'Parameter #1 $array (array{null, 0}) of array_values is already a list, call has no effect.',
				20,
			],
			[
				'Parameter #1 $array (array{}) to function array_values is empty, call has no effect.',
				21,
			],
		];

		if (PHP_VERSION_ID >= 80000) {
			$expectedErrors[] = [
				'Parameter #1 $array (list<int>) of array_values is already a list, call has no effect.',
				24,
			];
		}

		$this->analyse([__DIR__ . '/data/array_values_list.php'], $expectedErrors);
	}

}
