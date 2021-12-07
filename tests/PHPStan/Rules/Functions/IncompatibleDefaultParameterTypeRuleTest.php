<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatibleDefaultParameterTypeRule>
 */
class IncompatibleDefaultParameterTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleDefaultParameterTypeRule();
	}

	public function testFunctions(): void
	{
		require_once __DIR__ . '/data/incompatible-default-parameter-type-functions.php';
		$this->analyse([__DIR__ . '/data/incompatible-default-parameter-type-functions.php'], [
			[
				'Default value of the parameter #1 $string (false) of function IncompatibleDefaultParameter\takesString() is incompatible with type string.',
				15,
			],
		]);
	}

	public function testBug3349(): void
	{
		require_once __DIR__ . '/data/define-bug-3349.php';
		$this->analyse([__DIR__ . '/data/bug-3349.php'], []);
	}

}
