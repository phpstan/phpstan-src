<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;

/**
 * @implements Rule<ClosureReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
final class TooWideClosureReturnTypehintRule implements Rule
{

	public function __construct(
		private TooWideTypeCheck $check,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClosureReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$closureExpr = $node->getClosureExpr();
		if ($closureExpr->returnType === null) {
			return [];
		}

		$statementResult = $node->getStatementResult();
		if ($statementResult->hasYield()) {
			return [];
		}

		$returnStatements = $node->getReturnStatements();
		if (count($returnStatements) === 0) {
			return [];
		}

		$closureReturnType = $scope->getFunctionType($closureExpr->returnType, false, false);
		if (!$closureReturnType instanceof UnionType) {
			return [];
		}

		$returnTypes = [];
		foreach ($returnStatements as $returnStatement) {
			$returnNode = $returnStatement->getReturnNode();
			if ($returnNode->expr === null) {
				continue;
			}

			$returnTypes[] = $returnStatement->getScope()->getType($returnNode->expr);
		}

		if (count($returnTypes) === 0) {
			return [];
		}

		return $this->check->checkAnonymousFunction(TypeCombinator::union(...$returnTypes), $closureReturnType);
	}

}
