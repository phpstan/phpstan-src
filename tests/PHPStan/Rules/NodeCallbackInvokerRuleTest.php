<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NodeCallbackInvokerRule>
 */
class NodeCallbackInvokerRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NodeCallbackInvokerRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/node-callback-invoker.php'], [
			[
				'found virtual echo',
				6,
			],
			[
				'found echo',
				5,
			],
		]);
	}

}
