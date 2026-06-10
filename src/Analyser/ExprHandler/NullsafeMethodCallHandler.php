<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node;
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
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\ShouldNotHappenException;
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
		private MethodCallHandler $methodCallHandler,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
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
		return $context->true() ? $types->unionWith($nullSafeTypes) : $types->normalize($scope)->intersectWith($nullSafeTypes->normalize($scope));
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$scopeBeforeNullsafe = $scope;
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $varResult->getScope();
		$varType = $varResult->getType();

		// the only place that ever needs to know about `?->`: the subject was just
		// evaluated, narrow it non-null for the call part and revert after —
		// parents simply compose this result (NEW_WORLD.md §3.10)
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureShallowNonNullabilityFromTypes($scope, $expr->var, $varType, $varResult->getNativeType());
		$scope = $nonNullabilityResult->getScope();

		$attributes = array_merge($expr->getAttributes(), ['virtualNullsafeMethodCall' => true]);
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		$plainCall = new MethodCall($expr->var, $expr->name, $expr->args, $attributes);

		// rules see the virtual plain call as the old delegation provided, and their
		// asks about the subject must answer the narrowed type while the call part is
		// in flight — the old world re-evaluated the subject on the narrowed scope
		$narrowedVarResult = new ExpressionResult(
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			expr: $expr->var,
			typeCallback: static function (Expr $e, MutatingScope $s) use ($varResult): Type {
				$varType = $varResult->getTypeForScope($s);
				if ($varType->isNull()->yes()) {
					// an always-null subject is not narrowed (the call is reported instead)
					return $varType;
				}

				return TypeCombinator::removeNull($varType);
			},
		);
		$nodeScopeResolver->storeResult($storage, $expr->var, $narrowedVarResult);
		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, $plainCall, $scope, $storage, $context);
		$plainResult = $this->methodCallHandler->processCallWithVarResult(
			$nodeScopeResolver,
			$stmt,
			$plainCall,
			$varResult,
			TypeCombinator::removeNull($varType),
			$scope,
			$storage,
			$nodeCallback,
			$context,
		);
		$plainResult->setExpr($plainCall);
		$nodeScopeResolver->storeResult($storage, $plainCall, $plainResult);
		$nodeScopeResolver->storeResult($storage, $expr->var, $varResult);

		$scope = $this->nonNullabilityHelper->revertNonNullability($plainResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

		$varIsNull = $varType->isNull();
		if ($varIsNull->yes()) {
			// Arguments are never evaluated when the var is always null.
			$scope = $scopeBeforeNullsafe;
		} elseif ($varIsNull->maybe()) {
			// Arguments might not be evaluated (short-circuit).
			// Merge with the original scope so variables assigned in arguments become "maybe defined".
			$scope = $scope->mergeWith($scopeBeforeNullsafe);
		}

		$methodReflection = $expr->name instanceof Node\Identifier
			? $scope->getMethodReflection(TypeCombinator::removeNull($varType), $expr->name->toString())
			: null;
		$resultNarrowingAllowed = $methodReflection !== null
			&& !$methodReflection->hasSideEffects()->yes()
			&& ($this->rememberPossiblyImpureFunctionValues || $methodReflection->hasSideEffects()->no());

		// the call's own type bridges through the stored plain result until
		// MethodCallHandler migrates (PHPSTAN_FNSR=0) — then this composes for free
		$typeCallback = static function (Expr $e, MutatingScope $s) use ($varResult, $plainResult): Type {
			if (!$e instanceof NullsafeMethodCall) {
				throw new ShouldNotHappenException();
			}

			$varType = $varResult->getTypeForScope($s);
			if ($varType->isNull()->yes()) {
				return new NullType();
			}

			$methodReturnType = $plainResult->getTypeForScope($s);
			if (TypeCombinator::containsNull($varType)) {
				return TypeCombinator::union($methodReturnType, new NullType());
			}

			return $methodReturnType;
		};

		return new ExpressionResult(
			$scope,
			hasYield: $plainResult->hasYield(),
			isAlwaysTerminating: false,
			throwPoints: $plainResult->getThrowPoints(),
			impurePoints: $plainResult->getImpurePoints(),
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: $this->defaultNarrowingHelper->createNullsafeSpecifyCallback($expr, $varResult, $resultNarrowingAllowed, $plainCall, $nodeScopeResolver, $stmt),
			companionResults: [$scope->getNodeKey($plainCall) => $plainResult],
		);
	}

}
