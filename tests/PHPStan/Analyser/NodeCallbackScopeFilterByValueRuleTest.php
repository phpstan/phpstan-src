<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NodeCallbackScopeFilterByValueRule>
 */
class NodeCallbackScopeFilterByValueRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NodeCallbackScopeFilterByValueRule();
	}

	public function testFilterByValue(): void
	{
		$this->analyse([__DIR__ . '/data/node-callback-scope-filter-by-value.php'], [
			[
				'truthy: int, falsey: null',
				15,
			],
			[
				'chained: int<min, 4>|int<6, max>',
				20,
			],
		]);
	}

}
