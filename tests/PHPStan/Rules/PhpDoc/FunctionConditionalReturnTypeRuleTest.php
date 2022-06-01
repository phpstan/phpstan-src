<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FunctionConditionalReturnTypeRule>
 */
class FunctionConditionalReturnTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FunctionConditionalReturnTypeRule(new ConditionalReturnTypeRuleHelper());
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/function-conditional-return-type.php';
		$this->analyse([__DIR__ . '/data/function-conditional-return-type.php'], [
			[
				'Conditional return type uses subject type stdClass which is not part of PHPDoc @template tags.',
				37,
			],
			[
				'Conditional return type references unknown parameter $j.',
				45,
			],
			[
				'Condition "int is int" in conditional return type is always true.',
				53,
			],
			[
				'Condition "T of int is int" in conditional return type is always true.',
				63,
			],
			[
				'Condition "T of int is int" in conditional return type is always true.',
				73,
			],
			[
				'Condition "int is not int" in conditional return type is always false.',
				81,
			],
			[
				'Condition "int is string" in conditional return type is always false.',
				89,
			],
			[
				'Condition "T of int is string" in conditional return type is always false.',
				99,
			],
			[
				'Condition "T of int is string" in conditional return type is always false.',
				109,
			],
			[
				'Condition "int is not string" in conditional return type is always true.',
				117,
			],
			[
				'Condition "array<int> is int" in conditional return type is always false.',
				125,
			],
			[
				'Condition "array<int> is array<int>" in conditional return type is always true.',
				133,
			],
		]);
	}

}
