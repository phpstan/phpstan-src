<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\VerbosityLevel;

/**
 * A virtual node built from an expression's attributes (BooleanAndNode from a
 * BooleanAnd, say) used to inherit the expression's cached printed key, so
 * that it was tracked in the scope under the key of the expression it was
 * made from - but only when that expression happened to have been printed
 * first. Whatever was stored for the virtual node then answered for the
 * original expression.
 *
 * The rule asks for the type of every expression it is given, virtual nodes
 * included, like third-party rules and collectors commonly do.
 *
 * @extends RuleTestCase<Rule<Node>>
 */
class InheritedExpressionKeyRuleTest extends RuleTestCase
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
				if (!$node instanceof Expr) {
					return [];
				}

				$scope->getType($node);
				$scope->getNativeType($node);
				if (!$node instanceof BooleanAnd) {
					return [];
				}

				return [
					RuleErrorBuilder::message($scope->getKeepVoidType($node)->describe(VerbosityLevel::precise()))
						->identifier('tests.keepVoidType')
						->build(),
				];
			}

		};
	}

	public function testKeepVoidTypeOfBooleanAnd(): void
	{
		$this->analyse([__DIR__ . '/data/inherited-expression-key.php'], [
			['bool', 15],
		]);
	}

}
