<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use DivisionByZeroError;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;
use function get_class;
use function is_string;
use function sprintf;

/**
 * @implements ExprHandler<AssignOp>
 */
#[AutowiredService]
final class AssignOpHandler implements ExprHandler
{

	public function __construct(
		private AssignHandler $assignHandler,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof AssignOp;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$assignResult = $this->assignHandler->processAssignVar(
			$nodeScopeResolver,
			$scope,
			$storage,
			$stmt,
			$expr->var,
			$expr,
			$nodeCallback,
			$context,
			function (MutatingScope $scope) use ($stmt, $expr, $nodeCallback, $context, $storage, $nodeScopeResolver): ExpressionResult {
				$originalScope = $scope;
				if ($expr instanceof Expr\AssignOp\Coalesce) {
					$scope = $scope->filterByFalseyValue(
						new BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
					);

					if ($expr->var instanceof Expr\Variable && is_string($expr->var->name)) {
						$context = $context->enterRightSideAssign(
							$expr->var->name,
							$expr->expr,
						);
					}
				}

				$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
				if ($expr instanceof Expr\AssignOp\Coalesce) {
					$isAlwaysTerminating = $exprResult->isAlwaysTerminating() && $originalScope->getType($expr->var)->isNull()->yes();
					return $this->expressionResultFactory->create(
						$exprResult->getScope()->mergeWith($originalScope),
						$originalScope,
						$expr->expr,
						$exprResult->hasYield(),
						$isAlwaysTerminating,
						$exprResult->getThrowPoints(),
						$exprResult->getImpurePoints(),
					);
				}

				return $exprResult;
			},
			$expr instanceof Expr\AssignOp\Coalesce,
		);
		$scope = $assignResult->getScope();
		$throwPoints = $assignResult->getThrowPoints();
		$impurePoints = $assignResult->getImpurePoints();
		if (
			($expr instanceof Expr\AssignOp\Div || $expr instanceof Expr\AssignOp\Mod) &&
			!$scope->getType($expr->expr)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
		}
		if ($expr instanceof Expr\AssignOp\Concat) {
			$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($expr->expr, $scope);
			$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $assignResult->hasYield(),
			isAlwaysTerminating: $assignResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$getType = static fn (Expr $expr): Type => $scope->getType($expr);

		if ($expr instanceof Expr\AssignOp\Coalesce) {
			return $scope->getType(new BinaryOp\Coalesce($expr->var, $expr->expr, $expr->getAttributes()));
		}

		if ($expr instanceof Expr\AssignOp\Concat) {
			return $this->initializerExprTypeResolver->getConcatType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\BitwiseAnd) {
			return $this->initializerExprTypeResolver->getBitwiseAndType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\BitwiseOr) {
			return $this->initializerExprTypeResolver->getBitwiseOrType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\BitwiseXor) {
			return $this->initializerExprTypeResolver->getBitwiseXorType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\Div) {
			return $this->initializerExprTypeResolver->getDivType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\Mod) {
			return $this->initializerExprTypeResolver->getModType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\Plus) {
			return $this->initializerExprTypeResolver->getPlusType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\Minus) {
			return $this->initializerExprTypeResolver->getMinusType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\Mul) {
			return $this->initializerExprTypeResolver->getMulType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\Pow) {
			return $this->initializerExprTypeResolver->getPowType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\ShiftLeft) {
			return $this->initializerExprTypeResolver->getShiftLeftType($expr->var, $expr->expr, $getType);
		}

		if ($expr instanceof Expr\AssignOp\ShiftRight) {
			return $this->initializerExprTypeResolver->getShiftRightType($expr->var, $expr->expr, $getType);
		}

		throw new ShouldNotHappenException(sprintf('Unhandled %s', get_class($expr)));
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
