<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\MissingTypehintCheck;

/**
 * @extends \PHPStan\Testing\RuleTestCase<MissingFunctionReturnTypehintRule>
 */
class MissingFunctionReturnTypehintRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new MissingFunctionReturnTypehintRule($this->createBroker([], []), new MissingTypehintCheck(true));
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/missing-function-return-typehint.php';
		$this->analyse([__DIR__ . '/data/missing-function-return-typehint.php'], [
			[
				'Function globalFunction1() has no return typehint specified.',
				5,
			],
			[
				'Function MissingFunctionReturnTypehint\namespacedFunction1() has no return typehint specified.',
				30,
			],
			[
				'Function MissingFunctionReturnTypehint\unionTypeWithUnknownArrayValueTypehint() return type has no value type specified in iterable type array.',
				51,
			],
			[
				'Function MissingFunctionReturnTypehint\returnsGenericInterface() return type with generic interface MissingFunctionReturnTypehint\GenericInterface does not specify its types: T, U',
				70,
			],
			[
				'Function MissingFunctionReturnTypehint\returnsGenericClass() return type with generic class MissingFunctionReturnTypehint\GenericClass does not specify its types: A, B',
				89,
			],
		]);
	}

}
