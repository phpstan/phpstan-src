<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function count;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
final class TooWideFunctionReturnTypehintRule implements Rule
{

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$function = $node->getFunctionReflection();

		$functionReturnType = $function->getReturnType();
		$functionReturnType = TypeUtils::resolveLateResolvableTypes($functionReturnType);
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
				$returnTypes[] = new VoidType();
				continue;
			}

			$returnTypes[] = $returnStatement->getScope()->getType($returnNode->expr);
		}

		if (!$statementResult->isAlwaysTerminating()) {
			$returnTypes[] = new VoidType();
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
