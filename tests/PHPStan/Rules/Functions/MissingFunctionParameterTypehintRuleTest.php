<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingFunctionParameterTypehintRule>
 */
class MissingFunctionParameterTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingFunctionParameterTypehintRule(new MissingTypehintCheck(true, true, true, true, []), true);
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/missing-function-parameter-typehint.php';
		$this->analyse([__DIR__ . '/data/missing-function-parameter-typehint.php'], [
			[
				'Function globalFunction() has parameter $b with no type specified.',
				9,
			],
			[
				'Function globalFunction() has parameter $c with no type specified.',
				9,
			],
			[
				'Function MissingFunctionParameterTypehint\namespacedFunction() has parameter $d with no type specified.',
				24,
			],
			[
				'Function MissingFunctionParameterTypehint\missingArrayTypehint() has parameter $a with no value type specified in iterable type array.',
				36,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingPhpDocIterableTypehint() has parameter $a with no value type specified in iterable type array.',
				44,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\unionTypeWithUnknownArrayValueTypehint() has parameter $a with no value type specified in iterable type array.',
				60,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\acceptsGenericInterface() has parameter $i with generic interface MissingFunctionParameterTypehint\GenericInterface but does not specify its types: T, U',
				111,
			],
			[
				'Function MissingFunctionParameterTypehint\acceptsGenericClass() has parameter $c with generic class MissingFunctionParameterTypehint\GenericClass but does not specify its types: A, B',
				130,
			],
			[
				'Function MissingFunctionParameterTypehint\missingIterableTypehint() has parameter $iterable with no value type specified in iterable type iterable.',
				135,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingIterableTypehintPhpDoc() has parameter $iterable with no value type specified in iterable type iterable.',
				143,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingTraversableTypehint() has parameter $traversable with no value type specified in iterable type Traversable.',
				148,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingTraversableTypehintPhpDoc() has parameter $traversable with no value type specified in iterable type Traversable.',
				156,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingCallableSignature() has parameter $cb with no signature specified for callable.',
				161,
			],
			[
				'Function MissingParamOutType\oneArray() has @param-out PHPDoc tag for parameter $a with no value type specified in iterable type array.',
				173,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingParamOutType\generics() has @param-out PHPDoc tag for parameter $a with generic class ReflectionClass but does not specify its types: T',
				181,
			],
			[
				'Function MissingParamClosureThisType\generics() has @param-closure-this PHPDoc tag for parameter $cb with generic class ReflectionClass but does not specify its types: T',
				191,
			],
		]);
	}

}
