<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function array_merge;
use function count;

/**
 * @implements ExprHandler<StaticPropertyFetch>
 */
#[AutowiredService]
final class StaticPropertyFetchHandler implements ExprHandler
{

	public function __construct(
		private PropertyReflectionFinder $propertyReflectionFinder,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof StaticPropertyFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [
			new ImpurePoint(
				$scope,
				$expr,
				'staticPropertyAccess',
				'static property access',
				true,
			),
		];
		$isAlwaysTerminating = false;
		if ($expr->class instanceof Expr) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $classResult->hasYield();
			$throwPoints = $classResult->getThrowPoints();
			$impurePoints = $classResult->getImpurePoints();
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();
			$scope = $classResult->getScope();
		}
		if (!$expr->name instanceof VarLikeIdentifier) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
			$scope = $nameResult->getScope();
		}

		return $this->expressionResultFactory->create(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->name instanceof VarLikeIdentifier) {
			if ($scope->nativeTypesPromoted) {
				$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $scope);
				if ($propertyReflection === null) {
					return new ErrorType();
				}
				if (!$propertyReflection->hasNativeType()) {
					return new MixedType();
				}

				$nativeType = $propertyReflection->getNativeType();

				if ($expr->class instanceof Expr) {
					return NullsafeShortCircuitingHelper::getType($scope, $expr->class, $nativeType);
				}

				return $nativeType;
			}

			if ($expr->class instanceof Name) {
				$staticPropertyFetchedOnType = $scope->resolveTypeByName($expr->class);
			} else {
				$staticPropertyFetchedOnType = TypeCombinator::removeNull($scope->getType($expr->class))->getObjectTypeOrClassStringObjectType();
			}

			$fetchType = $this->propertyFetchType(
				$scope,
				$staticPropertyFetchedOnType,
				$expr->name->toString(),
				$expr,
			);
			if ($fetchType === null) {
				$fetchType = new ErrorType();
			}

			if ($expr->class instanceof Expr) {
				return NullsafeShortCircuitingHelper::getType($scope, $expr->class, $fetchType);
			}

			return $fetchType;
		}

		$nameType = $scope->getType($expr->name);
		if (count($nameType->getConstantStrings()) > 0) {
			return TypeCombinator::union(
				...array_map(static fn ($constantString) => $constantString->getValue() === '' ? new ErrorType() : $scope
					->filterByTruthyValue(new Identical($expr->name, new String_($constantString->getValue())))
					->getType(new Expr\StaticPropertyFetch($expr->class, new VarLikeIdentifier($constantString->getValue()))), $nameType->getConstantStrings()),
			);
		}

		return new MixedType();
	}

	private function propertyFetchType(MutatingScope $scope, Type $fetchedOnType, string $propertyName, StaticPropertyFetch $propertyFetch): ?Type
	{
		$propertyReflection = $scope->getStaticPropertyReflection($fetchedOnType, $propertyName);
		if ($propertyReflection === null) {
			return null;
		}

		if ($scope->isInWriteExpressionAssign($propertyFetch)) {
			return $propertyReflection->getWritableType();
		}

		return $propertyReflection->getReadableType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
