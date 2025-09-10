<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function get_class;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
#[RegisteredRule(level: 2)]
final class InvalidComparisonOperationRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private OperatorTypeSpecifyingExtensionRegistryProvider $operatorTypeSpecifyingExtensionRegistryProvider,
		#[AutowiredParameter(ref: '%featureToggles.checkExtensionsForComparisonOperators%')]
		private bool $checkExtensionsForComparisonOperators,
	)
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

		$isLeftNumberType = $this->isNumberType($scope, $node->left);
		$isRightNumberType = $this->isNumberType($scope, $node->right);
		if ($isLeftNumberType === $isRightNumberType) {
			return [];
		}

		$result = $this->operatorTypeSpecifyingExtensionRegistryProvider->getRegistry()->callOperatorTypeSpecifyingExtensions(
			$node,
			$scope->getType($node->left),
			$scope->getType($node->right),
		);

		if ($result !== null) {
			if (! $result instanceof ErrorType) {
				return [];
			}

			if ($this->checkExtensionsForComparisonOperators) {
				return $this->createError($node, $scope);
			}
		}

		if (
			($isLeftNumberType && (
				$this->isPossiblyNullableObjectType($scope, $node->right) || $this->isPossiblyNullableArrayType($scope, $node->right)
			))
			|| ($isRightNumberType && (
				$this->isPossiblyNullableObjectType($scope, $node->left) || $this->isPossiblyNullableArrayType($scope, $node->left)
			))
		) {
			return $this->createError($node, $scope);
		}

		return [];
	}

	private function isNumberType(Scope $scope, Node\Expr $expr): bool
	{
		$acceptedType = new UnionType([new IntegerType(), new FloatType()]);
		$onlyNumber = static fn (Type $type): bool => $acceptedType->isSuperTypeOf($type)->yes();

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
		$type = $scope->getType($expr);
		$acceptedType = new UnionType([new ObjectWithoutClassType(), new NullType()]);

		return !$type->isNull()->yes() && $acceptedType->isSuperTypeOf($type)->yes();
	}

	private function isPossiblyNullableArrayType(Scope $scope, Node\Expr $expr): bool
	{
		$type = $scope->getType($expr);
		$acceptedType = new UnionType([new ArrayType(new MixedType(), new MixedType()), new NullType()]);

		return !$type->isNull()->yes() && $acceptedType->isSuperTypeOf($type)->yes();
	}

	/** @return list<IdentifierRuleError> */
	private function createError(Node\Expr\BinaryOp $node, Scope $scope): array
	{
		switch (get_class($node)) {
			case Node\Expr\BinaryOp\Equal::class:
				$nodeType = 'equal';
				break;
			case Node\Expr\BinaryOp\NotEqual::class:
				$nodeType = 'notEqual';
				break;
			case Node\Expr\BinaryOp\Greater::class:
				$nodeType = 'greater';
				break;
			case Node\Expr\BinaryOp\GreaterOrEqual::class:
				$nodeType = 'greaterOrEqual';
				break;
			case Node\Expr\BinaryOp\Smaller::class:
				$nodeType = 'smaller';
				break;
			case Node\Expr\BinaryOp\SmallerOrEqual::class:
				$nodeType = 'smallerOrEqual';
				break;
			case Node\Expr\BinaryOp\Spaceship::class:
				$nodeType = 'spaceship';
				break;
			default:
				throw new ShouldNotHappenException();
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Comparison operation "%s" between %s and %s results in an error.',
				$node->getOperatorSigil(),
				$scope->getType($node->left)->describe(VerbosityLevel::value()),
				$scope->getType($node->right)->describe(VerbosityLevel::value()),
			))
				->line($node->left->getStartLine())
				->identifier(sprintf('%s.invalid', $nodeType))
				->build(),
		];
	}

}
