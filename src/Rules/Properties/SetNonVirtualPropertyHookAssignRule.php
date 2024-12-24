<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use function sprintf;

/**
 * @implements Rule<PropertyHookReturnStatementsNode>
 */
final class SetNonVirtualPropertyHookAssignRule implements Rule
{

	public function getNodeType(): string
	{
		return PropertyHookReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$hookNode = $node->getPropertyHookNode();
		if ($hookNode->name->toLowerString() !== 'set') {
			return [];
		}

		$hookReflection = $node->getHookReflection();
		if (!$hookReflection->isPropertyHook()) {
			throw new ShouldNotHappenException();
		}

		$propertyName = $hookReflection->getHookedPropertyName();
		$classReflection = $node->getClassReflection();
		$propertyReflection = $node->getPropertyReflection();
		if ($propertyReflection->isVirtual()->yes()) {
			return [];
		}

		$finalHookScope = null;
		foreach ($node->getExecutionEnds() as $executionEnd) {
			$statementResult = $executionEnd->getStatementResult();
			$endNode = $executionEnd->getNode();
			if ($statementResult->isAlwaysTerminating()) {
				if ($endNode instanceof Node\Stmt\Expression) {
					$exprType = $statementResult->getScope()->getType($endNode->expr);
					if ($exprType instanceof NeverType && $exprType->isExplicit()) {
						continue;
					}
				}
			}
			if ($finalHookScope === null) {
				$finalHookScope = $statementResult->getScope();
				continue;
			}

			$finalHookScope = $finalHookScope->mergeWith($statementResult->getScope());
		}

		foreach ($node->getReturnStatements() as $returnStatement) {
			if ($finalHookScope === null) {
				$finalHookScope = $returnStatement->getScope();
				continue;
			}
			$finalHookScope = $finalHookScope->mergeWith($returnStatement->getScope());
		}

		if ($finalHookScope === null) {
			return [];
		}

		$initExpr = new PropertyInitializationExpr($propertyName);
		$hasInit = $finalHookScope->hasExpressionType($initExpr);
		if ($hasInit->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Set hook for non-virtual property %s::$%s does not %sassign value to it.',
				$classReflection->getDisplayName(),
				$propertyName,
				$hasInit->maybe() ? 'always ' : '',
			))->identifier('propertySetHook.noAssign')->build(),
		];
	}

}
