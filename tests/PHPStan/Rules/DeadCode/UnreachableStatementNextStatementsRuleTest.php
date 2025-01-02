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
				$errors = [
					RuleErrorBuilder::message('First unreachable')
						->identifier('tests.nextUnreachableStatements')
						->build(),
				];

				foreach ($node->getNextStatements() as $nextStatement) {
					$errors[] = RuleErrorBuilder::message('Another unreachable')
						->line($nextStatement->getStartLine())
						->identifier('tests.nextUnreachableStatements')
						->build();
				}

				return $errors;
			}

		};
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/multiple_unreachable.php'], [
			[
				'First unreachable',
				14,
			],
			[
				'Another unreachable',
				15,
			],
			[
				'Another unreachable',
				17,
			],
			[
				'Another unreachable',
				22,
			],
		]);
	}

	public function testRuleTopLevel(): void
	{
		$this->analyse([__DIR__ . '/data/multiple_unreachable_top_level.php'], [
			[
				'First unreachable',
				9,
			],
			[
				'Another unreachable',
				10,
			],
			[
				'Another unreachable',
				17,
			],
		]);
	}

}
