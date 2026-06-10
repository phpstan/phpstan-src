<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticTypeFactory;
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
		private PropertyFetchHandler $propertyFetchHandler,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private ExprPrinter $exprPrinter,
		private PhpVersion $phpVersion,
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
		return $context->true() ? $types->unionWith($nullSafeTypes) : $types->normalize($scope)->intersectWith($nullSafeTypes->normalize($scope));
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $varResult->getScope();
		$hasYield = $varResult->hasYield();
		$throwPoints = $varResult->getThrowPoints();
		$impurePoints = $varResult->getImpurePoints();

		// the only place that ever needs to know about `?->`: the subject was just
		// evaluated, narrow it non-null for the property part and revert after —
		// parents simply compose this result (NEW_WORLD.md §3.10)
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureShallowNonNullabilityFromTypes($scope, $expr->var, $varResult->getType(), $varResult->getNativeType());
		$scope = $nonNullabilityResult->getScope();

		$attributes = array_merge($expr->getAttributes(), ['virtualNullsafePropertyFetch' => true]);
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		$plainFetch = new PropertyFetch($expr->var, $expr->name, $attributes);

		$varTypeWithoutNullCallback = static fn (Expr $e, MutatingScope $s): Type => TypeCombinator::removeNull($varResult->getTypeForScope($s));

		if ($expr->name instanceof Identifier) {
			$propertyName = $expr->name->toString();
			$propertyReflection = $scope->getInstancePropertyReflection(TypeCombinator::removeNull($varResult->getType()), $propertyName);
			if ($propertyReflection !== null && $this->phpVersion->supportsPropertyHooks()) {
				$propertyDeclaringClass = $propertyReflection->getDeclaringClass();
				if ($propertyDeclaringClass->hasNativeProperty($propertyName)) {
					$nativeProperty = $propertyDeclaringClass->getNativeProperty($propertyName);
					$throwPoints = array_merge($throwPoints, $nodeScopeResolver->getThrowPointsFromPropertyHook($scope, $plainFetch, $nativeProperty, 'get'));
				}
			}
		} else {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$scope = $nameResult->getScope();
			if ($this->phpVersion->supportsPropertyHooks()) {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $plainFetch);
			}
		}

		// rules keep seeing the virtual plain fetch, as the old delegation provided;
		// their getType() asks resolve from the stored result below
		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, $plainFetch, $scope, $storage, $context);
		$plainResult = new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: false,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			expr: $plainFetch,
			typeCallback: $this->propertyFetchHandler->createTypeCallbackForVarType($varTypeWithoutNullCallback),
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
		);
		$nodeScopeResolver->storeResult($storage, $plainFetch, $plainResult);

		$scope = $this->nonNullabilityHelper->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());

		$propertyTypeCallback = $this->propertyFetchHandler->createTypeCallbackForVarType($varTypeWithoutNullCallback);
		$typeCallback = static function (Expr $e, MutatingScope $s) use ($varResult, $propertyTypeCallback): Type {
			if (!$e instanceof NullsafePropertyFetch) {
				throw new ShouldNotHappenException();
			}

			$varType = $varResult->getTypeForScope($s);
			if ($varType->isNull()->yes()) {
				return new NullType();
			}

			$propertyType = $propertyTypeCallback($e, $s);
			if (TypeCombinator::containsNull($varType)) {
				return TypeCombinator::union($propertyType, new NullType());
			}

			return $propertyType;
		};

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: false,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: $this->createSpecifyTypesCallback($varResult),
			companionResults: [$scope->getNodeKey($plainFetch) => $plainResult],
		);
	}

	/**
	 * @return callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes
	 */
	private function createSpecifyTypesCallback(ExpressionResult $varResult): callable
	{
		return function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($varResult): SpecifiedTypes {
			if (!$e instanceof NullsafePropertyFetch) {
				throw new ShouldNotHappenException();
			}

			if ($ctx->null()) {
				return (new SpecifiedTypes([], []))->setRootExpr($e);
			}

			if (!$ctx->truthy()) {
				$removedType = StaticTypeFactory::truthy();
				$chainExecuted = false;
			} elseif (!$ctx->falsey()) {
				$removedType = StaticTypeFactory::falsey();
				// a truthy result cannot have come from the short-circuit null
				$chainExecuted = true;
			} else {
				return (new SpecifiedTypes([], []))->setRootExpr($e);
			}

			$sureNotTypes = [
				$this->exprPrinter->printExpr($e) => [$e, $removedType],
			];

			$varType = $varResult->getTypeForScope($s);
			$varCanBeNull = TypeCombinator::containsNull($varType);

			if ($chainExecuted || !$varCanBeNull) {
				// the plain-chain variant holds the same narrowing
				$plain = NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($e);
				if ($plain !== $e) {
					$sureNotTypes[$this->exprPrinter->printExpr($plain)] = [$plain, $removedType];
				}
			}

			if ($chainExecuted && $varCanBeNull) {
				// the chain executed, so the subject is not null
				$sureNotTypes[$this->exprPrinter->printExpr($e->var)] = [$e->var, new NullType()];
			}

			return (new SpecifiedTypes([], $sureNotTypes))->setRootExpr($e);
		};
	}

}
