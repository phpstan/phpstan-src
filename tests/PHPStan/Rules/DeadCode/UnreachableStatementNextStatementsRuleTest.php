<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule>
 */
class UnreachableStatementNextStatementsRuleTest extends RuleTestCase
{

	/**
	 * @return Rule<Node>
	 */
	protected function getRule(): Rule
	{
		return new class implements Rule {

			public function getNodeType(): string
			{
				return UnreachableStatementNode::class;
			}

			/**
			 * @param UnreachableStatementNode $node
			 */
			public function processNode(Node $node, Scope $scope): array
			{
				$totalNextStatements = count($node->getNextStatements());

				return [
					RuleErrorBuilder::message(sprintf("It has %d over first unreachable statements", $totalNextStatements))
						->identifier('tests.total.next.unreachable.statement')
						->build(),
				];
			}

		};
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/multiple_unreachable.php'], [
			[
				'It has 2 over first unreachable statements',
				14
			],
		]);
	}

}
