<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function array_merge;
use function count;

/**
 * @implements ExprHandler<PropertyFetch>
 */
#[AutowiredService]
final class PropertyFetchHandler implements ExprHandler
{

	public function __construct(
		private PhpVersion $phpVersion,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PropertyFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
		$hasYield = $varResult->hasYield();
		$throwPoints = $varResult->getThrowPoints();
		$impurePoints = $varResult->getImpurePoints();
		$isAlwaysTerminating = $varResult->isAlwaysTerminating();
		$scope = $varResult->getScope();
		if ($expr->name instanceof Identifier) {
			$propertyName = $expr->name->toString();
			$propertyHolderType = $varResult->getType();
			$propertyReflection = $scope->getInstancePropertyReflection($propertyHolderType, $propertyName);
			if ($propertyReflection !== null && $this->phpVersion->supportsPropertyHooks()) {
				$propertyDeclaringClass = $propertyReflection->getDeclaringClass();
				if ($propertyDeclaringClass->hasNativeProperty($propertyName)) {
					$nativeProperty = $propertyDeclaringClass->getNativeProperty($propertyName);
					$throwPoints = array_merge($throwPoints, $nodeScopeResolver->getThrowPointsFromPropertyHook($scope, $expr, $nativeProperty, 'get'));
				}
			}
		} else {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
			$scope = $nameResult->getScope();
			if ($this->phpVersion->supportsPropertyHooks()) {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			}
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			expr: $expr,
			typeCallback: $this->createTypeCallback($varResult),
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
		);
	}

	/**
	 * Shared with NullsafePropertyFetchHandler — it passes the var's type with
	 * null already removed and unions the null back itself.
	 *
	 * @param callable(Expr, MutatingScope): Type $varTypeCallback
	 */
	public function createTypeCallbackForVarType(callable $varTypeCallback): Closure
	{
		return function (Expr $e, MutatingScope $s) use ($varTypeCallback): Type {
			if (!$e instanceof PropertyFetch && !$e instanceof Expr\NullsafePropertyFetch) {
				throw new ShouldNotHappenException();
			}

			if (!$e->name instanceof Identifier) {
				// dynamic property names: guarded legacy bridge (PHPSTAN_FNSR=0)
				return $s->getType($e);
			}

			$varType = $varTypeCallback($e, $s);

			if ($s->nativeTypesPromoted) {
				$propertyReflection = $s->getInstancePropertyReflection($varType, $e->name->name);
				if ($propertyReflection === null) {
					return new ErrorType();
				}

				if (!$propertyReflection->hasNativeType()) {
					return new MixedType();
				}

				return $propertyReflection->getNativeType();
			}

			$returnType = $this->propertyFetchType($s, $varType, $e->name->name, $e);

			return $returnType ?? new ErrorType();
		};
	}

	private function createTypeCallback(ExpressionResult $varResult): Closure
	{
		// a nullsafe var that can be null short-circuits this fetch too; its
		// handler already produced the null-union — propagate one level, no
		// recursive chain walking (NEW_WORLD.md §3.10)
		$isShortcircuited = static function (Expr $e, MutatingScope $s) use ($varResult): bool {
			if (!$e instanceof PropertyFetch) {
				throw new ShouldNotHappenException();
			}

			return ($e->var instanceof Expr\NullsafePropertyFetch || $e->var instanceof Expr\NullsafeMethodCall)
				&& TypeCombinator::containsNull($varResult->getTypeForScope($s));
		};
		$inner = $this->createTypeCallbackForVarType(static function (Expr $e, MutatingScope $s) use ($varResult, $isShortcircuited): Type {
			$varType = $varResult->getTypeForScope($s);
			if ($isShortcircuited($e, $s)) {
				return TypeCombinator::removeNull($varType);
			}

			return $varType;
		});

		return static function (Expr $e, MutatingScope $s) use ($inner, $isShortcircuited): Type {
			$type = $inner($e, $s);
			if ($isShortcircuited($e, $s)) {
				return TypeCombinator::union($type, new NullType());
			}

			return $type;
		};
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->name instanceof Identifier) {
			if ($scope->nativeTypesPromoted) {
				$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $scope);
				if ($propertyReflection === null) {
					return new ErrorType();
				}

				if (!$propertyReflection->hasNativeType()) {
					return new MixedType();
				}

				$nativeType = $propertyReflection->getNativeType();

				return NullsafeShortCircuitingHelper::getType($scope, $expr->var, $nativeType);
			}

			$returnType = $this->propertyFetchType(
				$scope,
				$scope->getType($expr->var),
				$expr->name->name,
				$expr,
			);
			if ($returnType === null) {
				$returnType = new ErrorType();
			}

			return NullsafeShortCircuitingHelper::getType($scope, $expr->var, $returnType);
		}

		$nameType = $scope->getType($expr->name);
		if (count($nameType->getConstantStrings()) > 0) {
			return TypeCombinator::union(
				...array_map(static fn ($constantString) => $constantString->getValue() === '' ? new ErrorType() : $scope
					->filterByTruthyValue(new Expr\BinaryOp\Identical($expr->name, new String_($constantString->getValue())))
					->getType(
						new PropertyFetch($expr->var, new Identifier($constantString->getValue())),
					), $nameType->getConstantStrings()),
			);
		}

		return new MixedType();
	}

	private function propertyFetchType(MutatingScope $scope, Type $fetchedOnType, string $propertyName, PropertyFetch|Expr\NullsafePropertyFetch $propertyFetch): ?Type
	{
		$propertyReflection = $scope->getInstancePropertyReflection($fetchedOnType, $propertyName);
		if ($propertyReflection === null) {
			return null;
		}

		if ($scope->isInExpressionAssign($propertyFetch)) {
			return $propertyReflection->getWritableType();
		}

		return $propertyReflection->getReadableType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
