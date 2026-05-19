<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<GotoUndefinedLabelRule>
 */
class GotoUndefinedLabelRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new GotoUndefinedLabelRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/goto-undefined-label.php'], [
			[
				"Goto to undefined label 'nonexistent'.",
				15,
			],
			[
				"Goto to undefined label 'outside'.",
				22,
			],
			[
				"Goto to undefined label 'outside'.",
				32,
			],
			[
				"Goto to undefined label 'outside'.",
				42,
			],
			[
				"Goto to undefined label 'inside'.",
				52,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPropertyHook(): void
	{
		$this->analyse([__DIR__ . '/data/goto-undefined-label-property-hook.php'], [
			[
				"Goto to undefined label 'nonexistent'.",
				21,
			],
			[
				"Goto to undefined label 'outside'.",
				35,
			],
		]);
	}

}
