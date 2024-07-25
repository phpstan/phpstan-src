<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
final class TooWideMethodReturnTypehintRule implements Rule
{

	public function __construct(private bool $checkProtectedAndPublicMethods, private bool $alwaysCheckFinal)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->isInTrait()) {
			return [];
		}
		$method = $node->getMethodReflection();
		$isFirstDeclaration = $method->getPrototype()->getDeclaringClass() === $method->getDeclaringClass();
		if (!$method->isPrivate()) {
			if ($this->alwaysCheckFinal) {
				if (!$method->getDeclaringClass()->isFinal() && !$method->isFinal()->yes()) {
					if (!$this->checkProtectedAndPublicMethods) {
						return [];
					}

					if ($isFirstDeclaration) {
						return [];
					}
				}
			} elseif (!$this->checkProtectedAndPublicMethods) {
				return [];
			} elseif ($isFirstDeclaration && !$method->getDeclaringClass()->isFinal() && !$method->isFinal()->yes()) {
				return [];
			}
		}

		$methodReturnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();
		$methodReturnType = TypeUtils::resolveLateResolvableTypes($methodReturnType);
		if (!$methodReturnType instanceof UnionType) {
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
		if (
			!$method->isPrivate()
			&& ($returnType->isNull()->yes() || $returnType instanceof ConstantBooleanType)
			&& !$isFirstDeclaration
		) {
			return [];
		}

		$messages = [];
		foreach ($methodReturnType->getTypes() as $type) {
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
				'Method %s::%s() never returns %s so it can be removed from the return type.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
			))->identifier('return.unusedType')->build();
		}

		return $messages;
	}

}
