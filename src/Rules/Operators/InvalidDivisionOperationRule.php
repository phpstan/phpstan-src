<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr>
 */
final class InvalidDivisionOperationRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Expr\AssignOp\Div || $node instanceof Node\Expr\AssignOp\Mod) {
			$identifier = 'assignOp';
			$left = $node->var;
			$right = $node->expr;
		} elseif ($node instanceof Node\Expr\BinaryOp\Div || $node instanceof Node\Expr\BinaryOp\Mod) {
			$identifier = 'binaryOp';
			$left = $node->left;
			$right = $node->right;
		} else {
			return [];
		}

		$leftType = $scope->getType($left);
		$rightType = $scope->getType($right);

		$zeroType = new UnionType([new ConstantIntegerType(0), new ConstantFloatType(0.0)]);
		if (!$rightType->isSuperTypeOf($zeroType)->maybe()) {
			// yes() is handled by InvalidBinaryOperationRule
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Binary operation "%s" between %s and %s might result in an error.',
				$node instanceof Node\Expr\AssignOp\Div || $node instanceof Node\Expr\BinaryOp\Div ? '/' : '%',
				$leftType->describe(VerbosityLevel::value()),
				$rightType->describe(VerbosityLevel::value()),
			))
				->line($left->getStartLine())
				->identifier(sprintf('%s.invalid', $identifier))
				->build(),
		];
	}

}
