<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatibleArrowFunctionDefaultParameterTypeRule>
 */
class IncompatibleArrowFunctionDefaultParameterTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleArrowFunctionDefaultParameterTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-default-parameter-type-arrow-functions.php'], [
			[
				'Default value of the parameter #1 $i (string) of anonymous function is incompatible with type int.',
				13,
			],
		]);
	}

}
