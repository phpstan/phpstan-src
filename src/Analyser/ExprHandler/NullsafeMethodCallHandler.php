<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\NullsafeMethodCallExpressionNode;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<NullsafeMethodCall>
 */
#[AutowiredService]
final class NullsafeMethodCallHandler implements ExprHandler
{

	public function __construct(
		private NonNullabilityHelper $nonNullabilityHelper,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof NullsafeMethodCall;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$varType = $scope->getType($expr->var);
		if ($varType->isNull()->yes()) {
			return new NullType();
		}
		if (!TypeCombinator::containsNull($varType)) {
			return $scope->getType(new MethodCall($expr->var, $expr->name, $expr->args));
		}

		return TypeCombinator::union(
			$scope->filterByTruthyValue(new NotIdentical($expr->var, new ConstFetch(new Name('null'))))
				->getType(new MethodCall($expr->var, $expr->name, $expr->args)),
			new NullType(),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
		}

		$types = $typeSpecifier->specifyTypesInCondition(
			$scope,
			new BooleanAnd(
				new NotIdentical($expr->var, new ConstFetch(new Name('null'))),
				new MethodCall($expr->var, $expr->name, $expr->args),
			),
			$context,
		)->setRootExpr($expr);

		$nullSafeTypes = $typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		return $context->true() ? $types->unionWith($nullSafeTypes) : $types->intersectWith($nullSafeTypes);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$calledOnType = $scope->getScopeType($expr->var);
		$calledOnNativeType = $scope->getScopeNativeType($expr->var);
		$scopeBeforeNullsafe = $scope;
		$varType = $scope->getType($expr->var);

		$nonNullabilityResult = $this->nonNullabilityHelper->ensureShallowNonNullability($scope, $scope, $expr->var);
		$attributes = array_merge($expr->getAttributes(), ['virtualNullsafeMethodCall' => true]);
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		$exprResult = $nodeScopeResolver->processExprNode(
			$stmt,
			new MethodCall(
				$expr->var,
				$expr->name,
				$expr->args,
				$attributes,
			),
			$nonNullabilityResult->getScope(),
			$storage,
			$nodeCallback,
			$context,
		);
		$scope = $this->nonNullabilityHelper->revertNonNullability($exprResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

		$varIsNull = $varType->isNull();
		if ($varIsNull->yes()) {
			// Arguments are never evaluated when the var is always null.
			$scope = $scopeBeforeNullsafe;
		} elseif ($varIsNull->maybe()) {
			// Arguments might not be evaluated (short-circuit).
			// Merge with the original scope so variables assigned in arguments become "maybe defined".
			$scope = $scope->mergeWith($scopeBeforeNullsafe);
		}

		// the nullsafe operation is processed; emit a virtual node carrying the
		// receiver's entry-scope type so its rule does not re-ask the scope
		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new NullsafeMethodCallExpressionNode($expr, $calledOnType, $calledOnNativeType), $beforeScope, $storage, $context);

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: false,
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			containsNullsafe: true,
		);
	}

}
