<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MethodConditionalReturnTypeRule>
 */
class MethodConditionalReturnTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MethodConditionalReturnTypeRule(new ConditionalReturnTypeRuleHelper());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-conditional-return-type.php'], [
			[
				'Conditional return type uses subject type stdClass which is not part of PHPDoc @template tags.',
				48,
			],
			[
				'Conditional return type uses subject type TAboveClass which is not part of PHPDoc @template tags.',
				57,
			],
			[
				'Conditional return type references unknown parameter $j.',
				65,
			],
			[
				'Condition "int is int" in conditional return type is always true.',
				73,
			],
			[
				'Condition "T of int is int" in conditional return type is always true.',
				83,
			],
			[
				'Condition "T of int is int" in conditional return type is always true.',
				93,
			],
			[
				'Condition "int is not int" in conditional return type is always false.',
				101,
			],
			[
				'Condition "int is string" in conditional return type is always false.',
				114,
			],
			[
				'Condition "T of int is string" in conditional return type is always false.',
				124,
			],
			[
				'Condition "T of int is string" in conditional return type is always false.',
				134,
			],
			[
				'Condition "int is not string" in conditional return type is always true.',
				142,
			],
			[
				'Condition "array{foo: string} is array{foo: int}" in conditional return type is always false.',
				156,
			],
		]);
	}

}
