<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr>
 */
class InvalidUnaryOperationRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\UnaryPlus
			&& !$node instanceof Node\Expr\UnaryMinus
			&& !$node instanceof Node\Expr\BitwiseNot
		) {
			return [];
		}

		if ($scope->getType($node) instanceof ErrorType) {

			if ($node instanceof Node\Expr\UnaryPlus) {
				$operator = '+';
			} elseif ($node instanceof Node\Expr\UnaryMinus) {
				$operator = '-';
			} else {
				$operator = '~';
			}
			return [
				RuleErrorBuilder::message(sprintf(
					'Unary operation "%s" on %s results in an error.',
					$operator,
					$scope->getType($node->expr)->describe(VerbosityLevel::value()),
				))
					->line($node->expr->getStartLine())
					->identifier('unaryOp.invalid')
					->build(),
			];
		}

		return [];
	}

}
