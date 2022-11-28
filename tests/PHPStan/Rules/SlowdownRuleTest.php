<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule>
 */
class SlowdownRuleTest extends RuleTestCase
{

	/**
	 * @return Rule<Node>
	 */
	protected function getRule(): Rule
	{
		return new class implements Rule {

			public function getNodeType(): string
			{
				return Node::class;
			}

			/**
			 * @return string[]
			 */
			public function processNode(Node $node, Scope $scope): array
			{
				return [];
			}
		};
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/1.9.x-slowdown.php'], []);
	}

}
