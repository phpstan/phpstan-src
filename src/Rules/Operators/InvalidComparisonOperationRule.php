<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
class InvalidComparisonOperationRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
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

		if ($this->isNumberType($scope, $node->left) && $this->isNumberType($scope, $node->right)) {
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
					$scope->getType($node->right)->describe(VerbosityLevel::value()),
				))->line($node->left->getLine())->build(),
			];
		}

		return [];
	}

	private function isNumberType(Scope $scope, Node\Expr $expr): bool
	{
		$acceptedType = new UnionType([new IntegerType(), new FloatType()]);
		$onlyNumber = static fn (Type $type): bool => $acceptedType->accepts($type, true)->yes();

		$type = $this->ruleLevelHelper->findTypeToCheck($scope, $expr, '', $onlyNumber)->getType();

		if (
			$type instanceof ErrorType
			|| !$type->equals($scope->getType($expr))
		) {
			return false;
		}

		// SimpleXMLElement can be cast to number union type
		return !$acceptedType->isSuperTypeOf($type)->no() || $acceptedType->equals($type->toNumber());
	}

	private function isPossiblyNullableObjectType(Scope $scope, Node\Expr $expr): bool
	{
		$acceptedType = new ObjectWithoutClassType();

		$type = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$expr,
			'',
			static fn (Type $type): bool => $acceptedType->isSuperTypeOf($type)->yes(),
		)->getType();

		if ($type instanceof ErrorType) {
			return false;
		}

		if (TypeCombinator::containsNull($type) && !$type->isNull()->yes()) {
			$type = TypeCombinator::removeNull($type);
		}

		$isSuperType = $acceptedType->isSuperTypeOf($type);
		if ($type instanceof BenevolentUnionType) {
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
			static fn (Type $type): bool => $type->isArray()->yes(),
		)->getType();

		if (TypeCombinator::containsNull($type) && !$type->isNull()->yes()) {
			$type = TypeCombinator::removeNull($type);
		}

		return !($type instanceof ErrorType) && $type->isArray()->yes();
	}

}
