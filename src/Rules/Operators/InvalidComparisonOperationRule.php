<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\BinaryOp>
 */
class InvalidComparisonOperationRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	public function __construct(RuleLevelHelper $ruleLevelHelper)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return Node\Expr\BinaryOp::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\BinaryOp\Equal
			&& !$node instanceof Node\Expr\BinaryOp\NotEqual
			&& !$node instanceof Node\Expr\BinaryOp\Smaller
			&& !$node instanceof Node\Expr\BinaryOp\SmallerOrEqual
			&& !$node instanceof Node\Expr\BinaryOp\Greater
			&& !$node instanceof Node\Expr\BinaryOp\GreaterOrEqual
			&& !$node instanceof Node\Expr\BinaryOp\Spaceship
		) {
			return [];
		}

		if (
			($this->isNumberType($scope, $node->left) && (
				$this->isPossiblyNullableObjectType($scope, $node->right) || $this->isPossiblyNullableArrayType($scope, $node->right)
			))
			|| ($this->isNumberType($scope, $node->right) && (
				$this->isPossiblyNullableObjectType($scope, $node->left) || $this->isPossiblyNullableArrayType($scope, $node->left)
			))
		) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Comparison operation "%s" between %s and %s results in an error.',
					$node->getOperatorSigil(),
					$scope->getType($node->left)->describe(VerbosityLevel::value()),
					$scope->getType($node->right)->describe(VerbosityLevel::value())
				))->line($node->left->getLine())->build(),
			];
		}

		return [];
	}

	private function isNumberType(Scope $scope, Node\Expr $expr): bool
	{
		$acceptedType = new UnionType([new IntegerType(), new FloatType()]);
		$onlyNumber = static function (Type $type) use ($acceptedType): bool {
			return $acceptedType->accepts($type, true)->yes();
		};

		$type = $this->ruleLevelHelper->findTypeToCheck($scope, $expr, '', $onlyNumber, false)->getType();

		if (
			$type instanceof ErrorType
			|| !$type->equals($scope->getType($expr))
		) {
			return false;
		}

		return !$acceptedType->isSuperTypeOf($type)->no();
	}

	private function isPossiblyNullableObjectType(Scope $scope, Node\Expr $expr): bool
	{
		$acceptedType = new ObjectWithoutClassType();

		$type = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$expr,
			'',
			static function (Type $type) use ($acceptedType): bool {
				return $acceptedType->isSuperTypeOf($type)->yes();
			},
			false
		)->getType();

		if ($type instanceof ErrorType) {
			return false;
		}

		if (TypeCombinator::containsNull($type) && !$type instanceof NullType) {
			$type = TypeCombinator::removeNull($type);
		}

		$isSuperType = $acceptedType->isSuperTypeOf($type);
		if ($type instanceof \PHPStan\Type\BenevolentUnionType) {
			return !$isSuperType->no();
		}

		return $isSuperType->yes();
	}

	private function isPossiblyNullableArrayType(Scope $scope, Node\Expr $expr): bool
	{
		$type = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$expr,
			'',
			static function (Type $type): bool {
				return $type->isArray()->yes();
			},
			false
		)->getType();

		if (TypeCombinator::containsNull($type) && !$type instanceof NullType) {
			$type = TypeCombinator::removeNull($type);
		}

		return !($type instanceof ErrorType) && $type->isArray()->yes();
	}

}
