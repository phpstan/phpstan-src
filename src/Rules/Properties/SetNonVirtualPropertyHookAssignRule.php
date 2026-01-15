<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use function array_slice;
use function count;
use function sprintf;

/**
 * @implements Rule<PropertyHookReturnStatementsNode>
 */
#[RegisteredRule(level: 3)]
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

		$scopesToMerge = [];
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
			$scopesToMerge[] = $statementResult->getScope();
		}

		foreach ($node->getReturnStatements() as $returnStatement) {
			$scopesToMerge[] = $returnStatement->getScope();
		}

		if (count($scopesToMerge) === 0) {
			return [];
		}

		// @phpstan-ignore method.notFound
		$finalHookScope = $scopesToMerge[0]->mergeWith(...array_slice($scopesToMerge, 1));

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
