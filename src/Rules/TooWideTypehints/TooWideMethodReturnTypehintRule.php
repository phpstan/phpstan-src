<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class TooWideMethodReturnTypehintRule implements Rule
{

	public function __construct(private bool $checkProtectedAndPublicMethods)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $scope->getFunction();
		if (!$method instanceof MethodReflection) {
			throw new ShouldNotHappenException();
		}
		$isFirstDeclaration = $method->getPrototype()->getDeclaringClass() === $method->getDeclaringClass();
		if (!$method->isPrivate()) {
			if (!$this->checkProtectedAndPublicMethods) {
				return [];
			}
			if ($isFirstDeclaration && !$method->getDeclaringClass()->isFinal() && !$method->isFinal()->yes()) {
				return [];
			}
		}

		$methodReturnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();
		if (!$methodReturnType instanceof UnionType) {
			return [];
		}
		$statementResult = $node->getStatementResult();
		if ($statementResult->hasYield()) {
			return [];
		}

		$returnStatements = $node->getReturnStatements();
		if ($returnStatements === []) {
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

		if ($returnTypes === []) {
			return [];
		}

		$returnType = TypeCombinator::union(...$returnTypes);
		if (
			!$method->isPrivate()
			&& ($returnType instanceof NullType || $returnType instanceof ConstantBooleanType)
			&& !$isFirstDeclaration
		) {
			return [];
		}

		$messages = [];
		foreach ($methodReturnType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($returnType)->no()) {
				continue;
			}

			if ($type instanceof NullType && !$node->hasNativeReturnTypehint()) {
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
			))->build();
		}

		return $messages;
	}

}
