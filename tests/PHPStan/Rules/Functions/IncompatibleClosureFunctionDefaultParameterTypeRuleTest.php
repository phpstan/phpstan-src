<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<IncompatibleClosureDefaultParameterTypeRule>
 */
class IncompatibleClosureFunctionDefaultParameterTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleClosureDefaultParameterTypeRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/incompatible-default-parameter-type-closure.php'], [
			[
				'Default value of the parameter #1 $i (string) of anonymous function is incompatible with type int.',
				19,
			],
		]);
	}

}
