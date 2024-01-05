<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<ClosureReturnStatementsNode>
 */
class TooWideClosureReturnTypehintRule implements Rule
{

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

		$closureReturnType = $scope->getFunctionType($closureExpr->returnType, false, false);
		if (!$closureReturnType instanceof UnionType) {
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

		$returnType = TypeCombinator::union(...$returnTypes);
		if ($returnType->isNull()->yes()) {
			return [];
		}

		$messages = [];
		foreach ($closureReturnType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($returnType)->no()) {
				continue;
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Anonymous function never returns %s so it can be removed from the return type.',
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
			))->identifier('return.unusedType')->build();
		}

		return $messages;
	}

}
