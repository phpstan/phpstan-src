<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodReturnStatementsNode;
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
 * @implements Rule<MethodReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
final class TooWideMethodReturnTypehintRule implements Rule
{

	public function __construct(
		#[AutowiredParameter(ref: '%checkTooWideReturnTypesInProtectedAndPublicMethods%')]
		private bool $checkProtectedAndPublicMethods,
	)
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
			if (!$method->getDeclaringClass()->isFinal() && !$method->isFinal()->yes()) {
				if (!$this->checkProtectedAndPublicMethods) {
					return [];
				}

				if ($isFirstDeclaration) {
					return [];
				}
			}
		}

		$methodReturnType = $method->getReturnType();
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
				$returnTypes[] = new VoidType();
				continue;
			}

			$returnTypes[] = $returnStatement->getScope()->getType($returnNode->expr);
		}

		if (!$statementResult->isAlwaysTerminating()) {
			$returnTypes[] = new VoidType();
		}

		$returnType = TypeCombinator::union(...$returnTypes);
		if (
			!$isFirstDeclaration
			&& !$method->isPrivate()
			&& ($returnType->isNull()->yes() || $returnType->isTrue()->yes() || $returnType->isFalse()->yes())
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
