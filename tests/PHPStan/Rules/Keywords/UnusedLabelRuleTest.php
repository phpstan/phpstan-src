<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<UnusedLabelRule>
 */
class UnusedLabelRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedLabelRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-label.php'], [
			[
				"Label 'unused' is unused.",
				15,
			],
			[
				"Label 'unused1' is unused.",
				31,
			],
			[
				"Label 'unused2' is unused.",
				35,
			],
			[
				"Label 'outside' is unused.",
				41,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPropertyHook(): void
	{
		$this->analyse([__DIR__ . '/data/unused-label-property-hook.php'], [
			[
				"Label 'unused' is unused.",
				21,
			],
		]);
	}

}
