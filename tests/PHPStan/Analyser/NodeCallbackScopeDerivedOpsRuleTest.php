<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NodeCallbackScopeDerivedOpsRule>
 */
class NodeCallbackScopeDerivedOpsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NodeCallbackScopeDerivedOpsRule();
	}

	public function testDerivedOps(): void
	{
		$this->analyse([__DIR__ . '/data/node-callback-scope-derived-ops.php'], [
			[
				'assigned: \'assigned\'',
				23,
			],
			[
				'before string, after: \'assigned\'',
				28,
			],
			[
				'assigned: \'assigned\'',
				33,
			],
			[
				'filtered string, assigned: \'assigned\'',
				38,
			],
		]);
	}

}
