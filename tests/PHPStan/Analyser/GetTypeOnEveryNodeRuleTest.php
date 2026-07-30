<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Third-party collectors and rules commonly call $scope->getType() on every
 * expression they receive. Virtual nodes emitted to node callbacks must
 * therefore either not be expressions at all or be resolvable and printable -
 * an isset()/empty()/?? statement used to emit Expr-extending virtual nodes
 * with no printer method, crashing the type-cache key printer.
 *
 * @extends RuleTestCase<Rule<Node>>
 */
class GetTypeOnEveryNodeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new class implements Rule {

			public function getNodeType(): string
			{
				return Node::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				if ($node instanceof Expr) {
					$scope->getType($node);
				}

				return [];
			}

		};
	}

	public function testGetTypeOnEveryNode(): void
	{
		$this->analyse([__DIR__ . '/data/get-type-on-every-node.php'], []);
	}

}
