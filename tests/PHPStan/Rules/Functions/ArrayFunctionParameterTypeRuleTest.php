<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\TestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ArrayFunctionParameterTypeRule>
 */
final class ArrayFunctionParameterTypeRuleTest extends RuleTestCase
{

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/array_function_callback.php'], [
			[
				'Parameter 1 of callback in function array_map does not accept string',
				7,
			],
			[
				'Parameter 2 of callback in function array_reduce does not accept string',
				14
			],
			[
				'Parameter 1 of callback in function array_filter does not accept string',
				22
			],
		]);
	}

	protected function getRule(): Rule
	{
		return new ArrayFunctionParameterTypeRule();
	}

}
