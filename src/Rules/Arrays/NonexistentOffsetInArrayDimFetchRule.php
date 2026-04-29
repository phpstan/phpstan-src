<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Expr\ArrayDimFetch>
 */
#[RegisteredRule(level: 3)]
final class NonexistentOffsetInArrayDimFetchRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private NonexistentOffsetInArrayDimFetchCheck $nonexistentOffsetInArrayDimFetchCheck,
		#[AutowiredParameter]
		private bool $reportMaybes,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\ArrayDimFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->isImplicitArrayCreation($node, $scope)->yes()) {
			return [];
		}

		if ($node->dim !== null) {
			$dimType = $scope->getType($node->dim);
			$unknownClassPattern = sprintf('Access to offset %s on an unknown class %%s.', SprintfHelper::escapeFormatString($dimType->describe(VerbosityLevel::value())));
		} else {
			$dimType = null;
			$unknownClassPattern = 'Access to an offset on an unknown class %s.';
		}

		$isOffsetAccessibleTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->var),
			$unknownClassPattern,
			static fn (Type $type): bool => $type->isOffsetAccessible()->yes(),
		);
		$isOffsetAccessibleType = $isOffsetAccessibleTypeResult->getType();
		if ($isOffsetAccessibleType instanceof ErrorType) {
			return $isOffsetAccessibleTypeResult->getUnknownClassErrors();
		}

		if ($scope->hasExpressionType($node)->yes()) {
			return [];
		}

		$isOffsetAccessible = $isOffsetAccessibleType->isOffsetAccessible();

		if ($scope->isInExpressionAssign($node) && $isOffsetAccessible->yes()) {
			return [];
		}

		if ($scope->isUndefinedExpressionAllowed($node) && $isOffsetAccessibleType->isOffsetAccessLegal()->yes()) {
			return [];
		}

		if (!$isOffsetAccessible->yes()) {
			if ($isOffsetAccessible->no() || $this->reportMaybes) {
				if ($dimType !== null) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Cannot access offset %s on %s.',
							$dimType->describe(count($dimType->getConstantStrings()) > 0 ? VerbosityLevel::precise() : VerbosityLevel::value()),
							$isOffsetAccessibleType->describe(VerbosityLevel::value()),
						))->identifier('offsetAccess.nonOffsetAccessible')->build(),
					];
				}

				return [
					RuleErrorBuilder::message(sprintf(
						'Cannot access an offset on %s.',
						$isOffsetAccessibleType->describe(VerbosityLevel::typeOnly()),
					))->identifier('offsetAccess.nonOffsetAccessible')->build(),
				];
			}

			return [];
		}

		if ($dimType === null) {
			return [];
		}

		if (
			$node->dim instanceof Node\Expr\FuncCall
			&& !$node->dim->isFirstClassCallable()
			&& $node->dim->name instanceof Node\Name
			&& in_array($node->dim->name->toLowerString(), ['array_key_first', 'array_key_last'], true)
			&& count($node->dim->getArgs()) >= 1
		) {
			$arrayArg = $node->dim->getArgs()[0]->value;
			$arrayType = $scope->getType($arrayArg);
			if (
				$arrayArg instanceof Node\Expr\Variable
				&& $node->var instanceof Node\Expr\Variable
				&& is_string($arrayArg->name)
				&& $arrayArg->name === $node->var->name
				&& $arrayType->isArray()->yes()
				&& $arrayType->isIterableAtLeastOnce()->yes()
			) {
				return [];
			}
		}

		if (
			$node->dim instanceof Node\Expr\FuncCall
			&& $node->dim->name instanceof Node\Name
			&& $node->dim->name->toLowerString() === 'array_rand'
			&& count($node->dim->getArgs()) >= 1
		) {
			$numArg = null;
			$arrayArg = $node->dim->getArgs()[0]->value;
			if (count($node->dim->getArgs()) > 1) {
				$numArg = $node->dim->getArgs()[1]->value;
			}
			$one = new ConstantIntegerType(1);
			$arrayType = $scope->getType($arrayArg);

			if (
				$arrayArg instanceof Node\Expr\Variable
				&& $node->var instanceof Node\Expr\Variable
				&& is_string($arrayArg->name)
				&& $arrayArg->name === $node->var->name
				&& $arrayType->isArray()->yes()
				&& $arrayType->isIterableAtLeastOnce()->yes()
				&& ($numArg === null || $one->isSuperTypeOf($scope->getType($numArg))->yes())
			) {
				return [];
			}
		}

		if (
			$node->dim instanceof Node\Expr\BinaryOp\Minus
			&& $node->dim->left instanceof Node\Expr\FuncCall
			&& $node->dim->left->name instanceof Node\Name
			&& in_array($node->dim->left->name->toLowerString(), ['count', 'sizeof'], true)
			&& count($node->dim->left->getArgs()) >= 1
			&& $node->dim->right instanceof Node\Scalar\Int_
			&& $node->dim->right->value === 1
		) {
			$arrayArg = $node->dim->left->getArgs()[0]->value;
			$arrayType = $scope->getType($arrayArg);
			if (
				$arrayArg instanceof Node\Expr\Variable
				&& $node->var instanceof Node\Expr\Variable
				&& is_string($arrayArg->name)
				&& $arrayArg->name === $node->var->name
				&& $arrayType->isList()->yes()
				&& $arrayType->isIterableAtLeastOnce()->yes()
			) {
				return [];
			}
		}

		return $this->nonexistentOffsetInArrayDimFetchCheck->check(
			$scope,
			$node->var,
			$unknownClassPattern,
			$dimType,
		);
	}

	private function isImplicitArrayCreation(Node\Expr\ArrayDimFetch $node, Scope $scope): TrinaryLogic
	{
		$varNode = $node->var;
		while ($varNode instanceof ArrayDimFetch) {
			$varNode = $varNode->var;
		}

		if (!$varNode instanceof Variable) {
			return TrinaryLogic::createNo();
		}

		if (!is_string($varNode->name)) {
			return TrinaryLogic::createNo();
		}

		return $scope->hasVariableType($varNode->name)->negate();
	}

}
