<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\ParentNodeTypeOfBinaryOperationsVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<BinaryOp>
 */
final class UnusedBinaryOperationResultRule implements Rule
{

	public function getNodeType(): string
	{
		return BinaryOp::class;
	}

	/**
	 * @param BinaryOp $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->getAttribute(ParentNodeTypeOfBinaryOperationsVisitor::ATTRIBUTE_NAME) === Expression::class) {
			return [
				RuleErrorBuilder::message('Result of binary operation is not used.')
					->line($node->getLine())
					->nonIgnorable()
					->build(),
			];
		}
		return [];
	}

}
