<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
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
use PHPStan\Node\NullsafePropertyFetchExpressionNode;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<NullsafePropertyFetch>
 */
#[AutowiredService]
final class NullsafePropertyFetchHandler implements ExprHandler
{

	public function __construct(
		private NonNullabilityHelper $nonNullabilityHelper,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof NullsafePropertyFetch;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$varType = $scope->getType($expr->var);
		if ($varType->isNull()->yes()) {
			return new NullType();
		}
		if (!TypeCombinator::containsNull($varType)) {
			return $scope->getType(new PropertyFetch($expr->var, $expr->name));
		}

		return TypeCombinator::union(
			$scope->filterByTruthyValue(new NotIdentical($expr->var, new ConstFetch(new Name('null'))))
				->getType(new PropertyFetch($expr->var, $expr->name)),
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
				new PropertyFetch($expr->var, $expr->name),
			),
			$context,
		)->setRootExpr($expr);

		$nullSafeTypes = $typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		return $context->true() ? $types->unionWith($nullSafeTypes) : $types->intersectWith($nullSafeTypes);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context, ?Type $overriddenType): ExpressionResult
	{
		$beforeScope = $scope;
		$calledOnType = $scope->getScopeType($expr->var);
		$calledOnNativeType = $scope->getScopeNativeType($expr->var);
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureShallowNonNullability($scope, $scope, $expr->var);
		$attributes = array_merge($expr->getAttributes(), ['virtualNullsafePropertyFetch' => true]);
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		$exprResult = $nodeScopeResolver->processExprNode($stmt, new PropertyFetch(
			$expr->var,
			$expr->name,
			$attributes,
		), $nonNullabilityResult->getScope(), $storage, $nodeCallback, $context, null);
		$scope = $this->nonNullabilityHelper->revertNonNullability($exprResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

		// the nullsafe operation is processed; emit a virtual node carrying the
		// receiver's entry-scope type so its rule does not re-ask the scope
		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new NullsafePropertyFetchExpressionNode($expr, $calledOnType, $calledOnNativeType), $beforeScope, $storage, $context);

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
