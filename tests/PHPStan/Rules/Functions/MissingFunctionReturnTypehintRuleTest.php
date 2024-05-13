<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingFunctionReturnTypehintRule>
 */
class MissingFunctionReturnTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingFunctionReturnTypehintRule(new MissingTypehintCheck(true, true, true, true, []));
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/missing-function-return-typehint.php';
		$this->analyse([__DIR__ . '/data/missing-function-return-typehint.php'], [
			[
				'Function globalFunction1() has no return type specified.',
				5,
			],
			[
				'Function MissingFunctionReturnTypehint\namespacedFunction1() has no return type specified.',
				30,
			],
			[
				'Function MissingFunctionReturnTypehint\unionTypeWithUnknownArrayValueTypehint() return type has no value type specified in iterable type array.',
				51,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionReturnTypehint\returnsGenericInterface() return type with generic interface MissingFunctionReturnTypehint\GenericInterface does not specify its types: T, U',
				70,
			],
			[
				'Function MissingFunctionReturnTypehint\returnsGenericClass() return type with generic class MissingFunctionReturnTypehint\GenericClass does not specify its types: A, B',
				89,
			],
			[
				'Function MissingFunctionReturnTypehint\genericGenericMissingTemplateArgs() return type with generic class MissingFunctionReturnTypehint\GenericClass does not specify its types: A, B',
				105,
			],
			[
				'Function MissingFunctionReturnTypehint\closureWithNoPrototype() return type has no signature specified for Closure.',
				113,
			],
			[
				'Function MissingFunctionReturnTypehint\callableWithNoPrototype() return type has no signature specified for callable.',
				127,
			],
			[
				'Function MissingFunctionReturnTypehint\callableNestedNoPrototype() return type has no signature specified for callable.',
				141,
			],
		]);
	}

}
