<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Rule<ClosureReturnStatementsNode>
 */
final class ClosureReturnTypeRule implements Rule
{

	public function __construct(private FunctionReturnTypeCheck $returnTypeCheck)
	{
	}

	public function getNodeType(): string
	{
		return ClosureReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInAnonymousFunction()) {
			return [];
		}

		/** @var Type $returnType */
		$returnType = $scope->getAnonymousFunctionReturnType();
		$containsNull = TypeCombinator::containsNull($returnType);
		$hasNativeTypehint = $node->getClosureExpr()->returnType !== null;

		$messages = [];
		foreach ($node->getReturnStatements() as $returnStatement) {
			$returnNode = $returnStatement->getReturnNode();
			$returnExpr = $returnNode->expr;
			if ($returnExpr === null && $containsNull && !$hasNativeTypehint) {
				$returnExpr = new Node\Expr\ConstFetch(new Node\Name\FullyQualified('null'));
			}
			$returnMessages = $this->returnTypeCheck->checkReturnType(
				$returnStatement->getScope(),
				$returnType,
				$returnExpr,
				$returnNode,
				'Anonymous function should return %s but empty return statement found.',
				'Anonymous function with return type void returns %s but should not return anything.',
				'Anonymous function should return %s but returns %s.',
				'Anonymous function should never return but return statement found.',
				$node->isGenerator(),
			);

			foreach ($returnMessages as $returnMessage) {
				$messages[] = $returnMessage;
			}
		}

		return $messages;
	}

}
