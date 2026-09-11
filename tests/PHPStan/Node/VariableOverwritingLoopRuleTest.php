<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<VariableOverwritingLoopRule>
 */
class VariableOverwritingLoopRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new VariableOverwritingLoopRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/variable-overwriting-loop.php'], [
			[
				'Foreach overwrites $x.',
				22,
			],
			[
				'Foreach overwrites $x.',
				68,
			],
			[
				'Foreach overwrites $k.',
				78,
			],
			[
				'Foreach overwrites $x.',
				89,
			],
			[
				'Foreach overwrites $x.',
				97,
			],
			[
				'Foreach overwrites $b.',
				115,
			],
			[
				'Foreach overwrites $x.',
				125,
			],
			[
				'Foreach overwrites $arr.',
				138,
			],
			[
				'For loop overwrites $i.',
				145,
			],
			[
				'For loop overwrites $i.',
				157,
			],
			[
				'For loop overwrites $i.',
				182,
			],
			[
				'Foreach overwrites $x.',
				209,
			],
			[
				'Foreach overwrites $x.',
				218,
			],
		]);
	}

}
