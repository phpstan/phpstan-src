<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
class TooWideFunctionReturnTypehintRule implements Rule
{

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$function = $node->getFunctionReflection();

		$functionReturnType = ParametersAcceptorSelector::selectSingle($function->getVariants())->getReturnType();
		if (!$functionReturnType instanceof UnionType) {
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

		$messages = [];
		foreach ($functionReturnType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($returnType)->no()) {
				continue;
			}

			if ($type->isNull()->yes() && !$node->hasNativeReturnTypehint()) {
				foreach ($node->getExecutionEnds() as $executionEnd) {
					if ($executionEnd->getStatementResult()->isAlwaysTerminating()) {
						continue;
					}

					continue 2;
				}
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() never returns %s so it can be removed from the return type.',
				$function->getName(),
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
			))->identifier('return.unusedType')->build();
		}

		return $messages;
	}

}
