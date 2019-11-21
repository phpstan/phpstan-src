<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\MethodReturnStatementsNode>
 */
class TooWideMethodReturnTypehintRule implements Rule
{

	/** @var bool */
	private $checkPossibleCovariantMethodReturnType;

	public function __construct(bool $checkPossibleCovariantMethodReturnType)
	{
		$this->checkPossibleCovariantMethodReturnType = $checkPossibleCovariantMethodReturnType;
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $scope->getFunction();
		if (!$method instanceof MethodReflection) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$isFirstDeclaration = $method->getPrototype()->getDeclaringClass() === $method->getDeclaringClass();
		$showTip = false;
		if (!$method->isPrivate()) {
			if (!$isFirstDeclaration) {
				if (PHP_VERSION_ID < 70400 || !$this->checkPossibleCovariantMethodReturnType) {
					return [];
				} else {
					$showTip = true;
				}
			} elseif (!$method->getDeclaringClass()->isFinal() && !$method->isFinal()->yes()) {
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
			PHP_VERSION_ID >= 70400
			&& $this->checkPossibleCovariantMethodReturnType
			&& !$method->isPrivate()
			&& $returnType instanceof NullType
			&& !$isFirstDeclaration
		) {
			return [];
		}

		$messages = [];
		foreach ($methodReturnType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($returnType)->no()) {
				continue;
			}

			$builder = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() never returns %s so it can be removed from the return typehint.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$type->describe(VerbosityLevel::typeOnly())
			));
			if ($showTip) {
				$builder->tip('If you don\'t want to allow covariant return typehints even on PHP 7.4, turn this off with <fg=cyan>checkPossibleCovariantMethodReturnType: false</> in your <fg=cyan>%configurationFile%</>.');
			}
			$messages[] = $builder->build();
		}

		return $messages;
	}

}
