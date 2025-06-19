<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ScopeFunctionCallStackRule>
 */
class ScopeFunctionCallStackRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ScopeFunctionCallStackRule();
	}

	#[RequiresPhp('>= 8.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/scope-function-call-stack.php'], [
			[
				"var_dump\nprint_r\nsleep",
				7,
			],
			[
				"var_dump\nprint_r\nsleep",
				10,
			],
			[
				"var_dump\nprint_r\nsleep",
				13,
			],
		]);
	}

}
