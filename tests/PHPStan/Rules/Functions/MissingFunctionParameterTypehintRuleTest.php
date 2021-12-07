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
		$broker = $this->createReflectionProvider();
		return new MissingFunctionParameterTypehintRule(new MissingTypehintCheck($broker, true, true, true, []));
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
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingPhpDocIterableTypehint() has parameter $a with no value type specified in iterable type array.',
				44,
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\unionTypeWithUnknownArrayValueTypehint() has parameter $a with no value type specified in iterable type array.',
				60,
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\acceptsGenericInterface() has parameter $i with generic interface MissingFunctionParameterTypehint\GenericInterface but does not specify its types: T, U',
				111,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Function MissingFunctionParameterTypehint\acceptsGenericClass() has parameter $c with generic class MissingFunctionParameterTypehint\GenericClass but does not specify its types: A, B',
				130,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Function MissingFunctionParameterTypehint\missingIterableTypehint() has parameter $iterable with no value type specified in iterable type iterable.',
				135,
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingIterableTypehintPhpDoc() has parameter $iterable with no value type specified in iterable type iterable.',
				143,
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingTraversableTypehint() has parameter $traversable with no value type specified in iterable type Traversable.',
				148,
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingTraversableTypehintPhpDoc() has parameter $traversable with no value type specified in iterable type Traversable.',
				156,
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Function MissingFunctionParameterTypehint\missingCallableSignature() has parameter $cb with no signature specified for callable.',
				161,
			],
		]);
	}

}
