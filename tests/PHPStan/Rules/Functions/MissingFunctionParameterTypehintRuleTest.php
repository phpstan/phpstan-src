<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\MissingTypehintCheck;

/**
 * @extends \PHPStan\Testing\RuleTestCase<MissingFunctionParameterTypehintRule>
 */
class MissingFunctionParameterTypehintRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createBroker([], []);
		return new MissingFunctionParameterTypehintRule($broker, new MissingTypehintCheck($broker, true, true));
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/missing-function-parameter-typehint.php';
		$this->analyse([__DIR__ . '/data/missing-function-parameter-typehint.php'], [
			[
				'Function globalFunction() has parameter $b with no typehint specified.',
				9,
			],
			[
				'Function globalFunction() has parameter $c with no typehint specified.',
				9,
			],
			[
				'Function MissingFunctionParameterTypehint\namespacedFunction() has parameter $d with no typehint specified.',
				24,
			],
			[
				'Function MissingFunctionParameterTypehint\missingArrayTypehint() has parameter $a with no value type specified in iterable type array.',
				36,
			],
			[
				'Function MissingFunctionParameterTypehint\missingPhpDocIterableTypehint() has parameter $a with no value type specified in iterable type array.',
				44,
			],
			[
				'Function MissingFunctionParameterTypehint\unionTypeWithUnknownArrayValueTypehint() has parameter $a with no value type specified in iterable type array.',
				60,
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
			],
			[
				'Function MissingFunctionParameterTypehint\missingIterableTypehintPhpDoc() has parameter $iterable with no value type specified in iterable type iterable.',
				143,
			],
			[
				'Function MissingFunctionParameterTypehint\missingTraversableTypehint() has parameter $traversable with no value type specified in iterable type Traversable.',
				148,
			],
			[
				'Function MissingFunctionParameterTypehint\missingTraversableTypehintPhpDoc() has parameter $traversable with no value type specified in iterable type Traversable.',
				156,
			],
		]);
	}

}
