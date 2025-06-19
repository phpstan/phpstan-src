<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ScopeFunctionCallStackWithParametersRule>
 */
class ScopeFunctionCallStackWithParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ScopeFunctionCallStackWithParametersRule();
	}

	#[RequiresPhp('>= 8.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/scope-function-call-stack.php'], [
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				7,
			],
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				10,
			],
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				13,
			],
		]);
	}

}
