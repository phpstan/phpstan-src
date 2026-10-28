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

	#[RequiresPhp('>= 8.0.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/scope-function-call-stack.php'], [
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				7,
			],
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				13,
			],
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				19,
			],
			[
				// Named argument test - should report $notImmediate, not $immediate
				'ScopeFunctionCallStack\NamedArgumentTest::testMethod ($notImmediate)',
				37,
			],
			[
				// Named argument with missing required param - should still match by name
				'ScopeFunctionCallStack\NamedArgumentTest::testMethod ($notImmediate)',
				48,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug15377(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15377.php'], [
			[
				'Bug15377\inTransaction ($callback)',
				13,
			],
			[
				'Bug15377\inTransaction ($callback)',
				17,
			],
			[
				'Bug15377\inTransaction ($callback)',
				27,
			],
			[
				'Bug15377\inTransaction ($callback)',
				28,
			],
			[
				'Bug15377\inTransaction ($callback)',
				29,
			],
			[
				'Bug15377\inTransaction ($callback)',
				43,
			],
			[
				"Bug15377\\inTransaction (\$callback)\nBug15377\\takesTwo (\$j)",
				44,
			],
			[
				'Bug15377\inTransaction ($callback)',
				52,
			],
			[
				'Bug15377\inTransaction ($callback)',
				62,
			],
			[
				'Bug15377\inTransaction ($callback)',
				63,
			],
			[
				'Bug15377\inTransaction ($callback)',
				64,
			],
			[
				'Bug15377\inTransaction ($callback)',
				65,
			],
			[
				"Bug15377\\inTransaction (\$callback)\nBug15377\\takesTwo (\$j)",
				66,
			],
			[
				'Bug15377\inTransaction ($callback)',
				67,
			],
			[
				'Bug15377\inTransaction ($callback)',
				68,
			],
			[
				'Bug15377\inTransaction ($callback)',
				69,
			],
			[
				'Bug15377\inTransaction ($callback)',
				70,
			],
			[
				'Bug15377\inTransaction ($callback)',
				71,
			],
			[
				'Bug15377\inTransaction ($callback)',
				72,
			],
		]);
	}

}
