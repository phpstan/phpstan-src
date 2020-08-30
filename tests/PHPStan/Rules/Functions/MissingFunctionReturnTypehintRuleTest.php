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
		$broker = $this->createReflectionProvider();
		return new MissingFunctionReturnTypehintRule(new MissingTypehintCheck($broker, true, true, true));
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
				"Consider adding something like <fg=cyan>array<Foo></> to the PHPDoc.\nYou can turn off this check by setting <fg=cyan>checkMissingIterableValueType: false</> in your <fg=cyan>%configurationFile%</>.",
			],
			[
				'Function MissingFunctionReturnTypehint\returnsGenericInterface() return type with generic interface MissingFunctionReturnTypehint\GenericInterface does not specify its types: T, U',
				70,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Function MissingFunctionReturnTypehint\returnsGenericClass() return type with generic class MissingFunctionReturnTypehint\GenericClass does not specify its types: A, B',
				89,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Function MissingFunctionReturnTypehint\genericGenericMissingTemplateArgs() return type with generic class MissingFunctionReturnTypehint\GenericClass does not specify its types: A, B',
				105,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Function MissingFunctionReturnTypehint\closureWithNoPrototype() return type has no prototype specified for callable type Closure.',
				113,
			],
			[
				'Function MissingFunctionReturnTypehint\callableWithNoPrototype() return type has no prototype specified for callable type callable.',
				127,
			],
			[
				'Function MissingFunctionReturnTypehint\callableNestedNoPrototype() return type has no prototype specified for callable type callable.',
				141,
			],
		]);
	}

}
