<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class InvalidUnaryOperationRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		if (
			!$node instanceof \PhpParser\Node\Expr\UnaryPlus
			&& !$node instanceof \PhpParser\Node\Expr\UnaryMinus
			&& !$node instanceof \PhpParser\Node\Expr\BitwiseNot
		) {
			return [];
		}

		if ($scope->getType($node) instanceof ErrorType) {

			if ($node instanceof \PhpParser\Node\Expr\UnaryPlus) {
				$operator = '+';
			} elseif ($node instanceof \PhpParser\Node\Expr\UnaryMinus) {
				$operator = '-';
			} else {
				$operator = '~';
			}
			return [
				RuleErrorBuilder::message(sprintf(
					'Unary operation "%s" on %s results in an error.',
					$operator,
					$scope->getType($node->expr)->describe(VerbosityLevel::value())
				))->line($node->expr->getLine())->build(),
			];
		}

		return [];
	}

}
