<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use DivisionByZeroError;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;
use function get_class;
use function is_string;
use function sprintf;

/**
 * @implements ExprHandler<BinaryOp>
 */
#[AutowiredService]
final class BinaryOpHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private RicherScopeGetTypeHelper $richerScopeGetTypeHelper,
		private PhpVersion $phpVersion,
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BinaryOp
			&& !$expr instanceof BooleanAnd
			&& !$expr instanceof BinaryOp\LogicalAnd
			&& !$expr instanceof BooleanOr
			&& !$expr instanceof BinaryOp\LogicalOr
			&& !$expr instanceof BinaryOp\Coalesce
			&& !$expr instanceof BinaryOp\Pipe;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$leftResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $leftResult->getScope(), $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints());
		$impurePoints = array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints());
		if (
			($expr instanceof BinaryOp\Div || $expr instanceof BinaryOp\Mod) &&
			!$leftResult->getScope()->getType($expr->right)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($leftResult->getScope(), new ObjectType(DivisionByZeroError::class), $expr, false);
		}
		if ($expr instanceof BinaryOp\Concat) {
			$leftToStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->left, $scope);
			$rightToStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->right, $leftResult->getScope());
			$throwPoints = array_merge($throwPoints, $leftToStringResult->getThrowPoints(), $rightToStringResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $leftToStringResult->getImpurePoints(), $rightToStringResult->getImpurePoints());
		}
		$scope = $rightResult->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating() || $rightResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$getType = static fn (Expr $expr): Type => $scope->getType($expr);

		if ($expr instanceof BinaryOp\Smaller) {
			return $scope->getType($expr->left)->isSmallerThan($scope->getType($expr->right), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof BinaryOp\SmallerOrEqual) {
			return $scope->getType($expr->left)->isSmallerThanOrEqual($scope->getType($expr->right), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof BinaryOp\Greater) {
			return $scope->getType($expr->right)->isSmallerThan($scope->getType($expr->left), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof BinaryOp\GreaterOrEqual) {
			return $scope->getType($expr->right)->isSmallerThanOrEqual($scope->getType($expr->left), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof BinaryOp\Equal) {
			if (
				$expr->left instanceof Variable
				&& is_string($expr->left->name)
				&& $expr->right instanceof Variable
				&& is_string($expr->right->name)
				&& $expr->left->name === $expr->right->name
			) {
				return new ConstantBooleanType(true);
			}

			$leftType = $scope->getType($expr->left);
			$rightType = $scope->getType($expr->right);

			return $this->initializerExprTypeResolver->resolveEqualType($leftType, $rightType)->type;
		}

		if ($expr instanceof BinaryOp\NotEqual) {
			return $scope->getType(new Expr\BooleanNot(new BinaryOp\Equal($expr->left, $expr->right)));
		}

		if ($expr instanceof BinaryOp\Identical) {
			return $this->richerScopeGetTypeHelper->getIdenticalResult($scope, $expr)->type;
		}

		if ($expr instanceof BinaryOp\NotIdentical) {
			return $this->richerScopeGetTypeHelper->getNotIdenticalResult($scope, $expr)->type;
		}

		if ($expr instanceof BinaryOp\LogicalXor) {
			$leftBooleanType = $scope->getType($expr->left)->toBoolean();
			$rightBooleanType = $scope->getType($expr->right)->toBoolean();

			if (
				$leftBooleanType instanceof ConstantBooleanType
				&& $rightBooleanType instanceof ConstantBooleanType
			) {
				return new ConstantBooleanType(
					$leftBooleanType->getValue() xor $rightBooleanType->getValue(),
				);
			}

			return new BooleanType();
		}

		if ($expr instanceof BinaryOp\Spaceship) {
			return $this->initializerExprTypeResolver->getSpaceshipType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\Concat) {
			return $this->initializerExprTypeResolver->getConcatType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\BitwiseAnd) {
			return $this->initializerExprTypeResolver->getBitwiseAndType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\BitwiseOr) {
			return $this->initializerExprTypeResolver->getBitwiseOrType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\BitwiseXor) {
			return $this->initializerExprTypeResolver->getBitwiseXorType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\Div) {
			return $this->initializerExprTypeResolver->getDivType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\Mod) {
			return $this->initializerExprTypeResolver->getModType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\Plus) {
			return $this->initializerExprTypeResolver->getPlusType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\Minus) {
			return $this->initializerExprTypeResolver->getMinusType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\Mul) {
			return $this->initializerExprTypeResolver->getMulType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\Pow) {
			return $this->initializerExprTypeResolver->getPowType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\ShiftLeft) {
			return $this->initializerExprTypeResolver->getShiftLeftType($expr->left, $expr->right, $getType);
		}

		if ($expr instanceof BinaryOp\ShiftRight) {
			return $this->initializerExprTypeResolver->getShiftRightType($expr->left, $expr->right, $getType);
		}

		throw new ShouldNotHappenException(sprintf('Unhandled %s', get_class($expr)));
	}

}
