<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NodeConnectingRule>
 */
class NodeConnectingRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NodeConnectingRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/node-connecting.php'], [
			[
				'Parent: PhpParser\Node\Stmt\If_',
				11,
			],
		]);
	}

}
