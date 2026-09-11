<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedParametersCheck;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedMethodParametersRule>
 */
class UnusedMethodParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedMethodParametersRule(self::getContainer()->getByType(UnusedParametersCheck::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-method-parameters.php'], [
			[
				'Method UnusedMethodParameters\Foo::completelyUnused() has an unused parameter $unused.',
				24,
			],
			[
				'Method UnusedMethodParameters\Foo::immediatelyReassigned() has an unused parameter $x.',
				29,
			],
			[
				'Method UnusedMethodParameters\Foo::byRefUnused() has an unused parameter $x.',
				57,
			],
			[
				'Method UnusedMethodParameters\Foo::staticCompletelyUnused() has an unused parameter $unused.',
				69,
			],
		]);
	}

	public function testParameterReadOnlyInCatchOfOverridingThrows(): void
	{
		$this->analyse([__DIR__ . '/data/unused-method-parameters-overriding-throws.php'], []);
	}

	public function testParameterCapturedByReference(): void
	{
		$this->analyse([__DIR__ . '/data/unused-method-parameters-by-ref-use.php'], [
			[
				'Method UnusedMethodParametersByRefUse\Foo::capturedByReferenceAfterOverwrite() has an unused parameter $expectedCalls.',
				23,
			],
		]);
	}

	public function testValueFlow(): void
	{
		$this->analyse([__DIR__ . '/data/unused-method-parameters-value-flow.php'], [
			['Method UnusedMethodParametersValueFlow\Foo::unusedParameter() has a parameter $input that only flows into values that are never used.', 8],
			['Method UnusedMethodParametersValueFlow\Foo::coveredParameter() has a parameter $input that only flows into values that are never used.', 15],
			['Method UnusedMethodParametersValueFlow\Foo::overwrittenParameter() has an unused parameter $input.', 26],
		]);
	}

}
