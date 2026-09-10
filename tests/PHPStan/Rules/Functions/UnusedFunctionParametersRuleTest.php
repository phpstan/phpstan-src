<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedParametersCheck;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedFunctionParametersRule>
 */
class UnusedFunctionParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedFunctionParametersRule(self::getContainer()->getByType(UnusedParametersCheck::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-function-parameters.php'], [
			[
				'Function UnusedFunctionParameters\completelyUnused() has an unused parameter $unused.',
				5,
			],
			[
				'Function UnusedFunctionParameters\immediatelyReassigned() has an unused parameter $x.',
				10,
			],
			[
				'Function UnusedFunctionParameters\byRefUnused() has an unused parameter $x.',
				38,
			],
			[
				'Function UnusedFunctionParameters\variadicUnused() has an unused parameter $rest.',
				50,
			],
			[
				'Function UnusedFunctionParameters\usedViaResolvedVariableVariable() has an unused parameter $other.',
				79,
			],
		]);
	}

}
