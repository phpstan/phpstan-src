<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\IssetabilityDescriptor;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Rules\Properties\FoundPropertyReflection;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
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
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof StaticPropertyFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$classResult = null;
		if ($expr->class instanceof Expr) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $classResult->getScope();
		}
		$nameResult = null;
		if (!$expr->name instanceof VarLikeIdentifier) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
		}

		return $this->composeResult($expr, $classResult, $nameResult, $beforeScope);
	}

	/**
	 * Builds the static property read's ExpressionResult from the
	 * already-walked class and name results - the fetch is not re-walked.
	 * processExpr() routes through this; AssignHandler::prepareTarget() calls
	 * it to price a read-modify-write target from the write walk's child
	 * results.
	 */
	public function composeResult(StaticPropertyFetch $expr, ?ExpressionResult $classResult, ?ExpressionResult $nameResult, MutatingScope $beforeScope): ExpressionResult
	{
		$scope = $beforeScope;
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
		if ($classResult !== null) {
			$hasYield = $classResult->hasYield();
			$throwPoints = $classResult->getThrowPoints();
			$impurePoints = $classResult->getImpurePoints();
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();
			$scope = $classResult->getScope();
		}
		if ($nameResult !== null) {
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
			$scope = $nameResult->getScope();
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			containsNullsafe: $classResult !== null && $classResult->containsNullsafe(),
			issetabilityDescriptor: IssetabilityDescriptor::property($classResult, fn (MutatingScope $s): ?FoundPropertyReflection => $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $s), $expr),
			typeCallback: function (bool $nativeTypesPromoted) use ($expr, $classResult, $nameResult, $beforeScope): Type {
				$classType = $classResult !== null
					? ($nativeTypesPromoted ? $classResult->getNativeType() : $classResult->getType())
					: null;
				$shortCircuit = static fn (Type $type): Type => $classResult !== null && $classResult->containsNullsafe() && $classType !== null && TypeCombinator::containsNull($classType)
					? TypeCombinator::addNull($type)
					: $type;

				// the property's class/visibility/assign context is lexical, so it
				// comes from beforeScope; the scope-dependent class-expression type
				// is read from the operand result above, and the native-vs-phpdoc
				// distinction comes from that type and the reflection accessor below.
				$reflectionScope = $beforeScope;
				if ($expr->class instanceof Name) {
					$staticPropertyFetchedOnType = $reflectionScope->resolveTypeByName($expr->class);
				} else {
					// every caller walks a non-Name class and passes its result
					if ($classType === null) {
						throw new ShouldNotHappenException();
					}
					$staticPropertyFetchedOnType = TypeCombinator::removeNull($classType)->getObjectTypeOrClassStringObjectType();
				}

				$resolveProperty = function (string $propertyName) use ($nativeTypesPromoted, $reflectionScope, $staticPropertyFetchedOnType, $expr): Type {
					if ($nativeTypesPromoted) {
						$propertyReflection = $reflectionScope->getStaticPropertyReflection($staticPropertyFetchedOnType, $propertyName);
						if ($propertyReflection === null) {
							return new ErrorType();
						}
						if (!$propertyReflection->hasNativeType()) {
							return new MixedType();
						}

						return $propertyReflection->getNativeType();
					}

					return $this->propertyFetchType($reflectionScope, $staticPropertyFetchedOnType, $propertyName, $expr) ?? new ErrorType();
				};

				if ($expr->name instanceof VarLikeIdentifier) {
					return $shortCircuit($resolveProperty($expr->name->toString()));
				}

				// dynamic static property fetch Foo::${$name}: resolve each possible
				// name from beforeScope. The asking scope is not narrowed per name,
				// so such fetches can be less precise.
				// every caller walks a non-VarLikeIdentifier name and passes its result
				if ($nameResult === null) {
					throw new ShouldNotHappenException();
				}
				$nameType = $nativeTypesPromoted ? $nameResult->getNativeType() : $nameResult->getType();
				if (count($nameType->getConstantStrings()) > 0) {
					return TypeCombinator::union(
						...array_map(static function ($constantString) use ($resolveProperty): Type {
							if ($constantString->getValue() === '') {
								return new ErrorType();
							}

							return $resolveProperty($constantString->getValue());
						}, $nameType->getConstantStrings()),
					);
				}

				return new MixedType();
			},
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
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

}
