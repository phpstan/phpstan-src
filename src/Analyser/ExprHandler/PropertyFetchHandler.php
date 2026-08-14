<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\IssetabilityDescriptor;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\PropertyHookThrowPointsResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
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
 * @implements ExprHandler<PropertyFetch>
 */
#[AutowiredService]
final class PropertyFetchHandler implements ExprHandler
{

	public function __construct(
		private PhpVersion $phpVersion,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private ExpressionResultFactory $expressionResultFactory,
		private PropertyHookThrowPointsResolver $propertyHookThrowPointsResolver,
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
		$beforeScope = $scope;
		$scopeBeforeVar = $scope;
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
		$nameResult = null;
		if (!$expr->name instanceof Identifier) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $varResult->getScope(), $storage, $nodeCallback, $context->enterDeep());
		}

		return $this->composeResult($nodeScopeResolver, $expr, $varResult, $nameResult, $scopeBeforeVar, $beforeScope);
	}

	/**
	 * Builds the property read's ExpressionResult from the already-walked
	 * receiver and name results - the fetch is not re-walked. processExpr()
	 * routes through this; AssignHandler::prepareTarget() calls it to price a
	 * read-modify-write target from the write walk's child results.
	 */
	public function composeResult(NodeScopeResolver $nodeScopeResolver, PropertyFetch $expr, ExpressionResult $varResult, ?ExpressionResult $nameResult, MutatingScope $scopeBeforeVar, MutatingScope $beforeScope): ExpressionResult
	{
		$hasYield = $varResult->hasYield();
		$throwPoints = $varResult->getThrowPoints();
		$impurePoints = $varResult->getImpurePoints();
		$isAlwaysTerminating = $varResult->isAlwaysTerminating();
		$scope = $varResult->getScope();
		if ($expr->name instanceof Identifier) {
			if ($this->phpVersion->supportsPropertyHooks()) {
				$propertyName = $expr->name->toString();
				$propertyHolderType = $varResult->getType();
				$propertyReflection = $scopeBeforeVar->getInstancePropertyReflection($propertyHolderType, $propertyName);
				if ($propertyReflection !== null) {
					$propertyDeclaringClass = $propertyReflection->getDeclaringClass();
					if ($propertyDeclaringClass->hasNativeProperty($propertyName)) {
						$nativeProperty = $propertyDeclaringClass->getNativeProperty($propertyName);
						$throwPoints = array_merge($throwPoints, $this->propertyHookThrowPointsResolver->getThrowPointsFromPropertyHook($scopeBeforeVar, $expr, $nativeProperty, 'get'));
					}
				}
			}
		} elseif ($nameResult !== null) {
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
			$scope = $nameResult->getScope();
			if ($this->phpVersion->supportsPropertyHooks()) {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			}
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			containsNullsafe: $varResult->containsNullsafe(),
			issetabilityDescriptor: IssetabilityDescriptor::property($varResult, fn (MutatingScope $s): ?FoundPropertyReflection => $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $s), $expr),
			typeCallback: function (bool $nativeTypesPromoted) use ($expr, $varResult, $nameResult, $beforeScope): Type {
				// a fetch on a nullsafe chain whose receiver is currently nullable
				// short-circuits to null - the receiver result carries whether the
				// chain contains a ?-> (a plain nullable receiver does not propagate)
				$receiverType = $nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType();
				$shortCircuit = static fn (Type $type): Type => $varResult->containsNullsafe() && TypeCombinator::containsNull($receiverType)
					? TypeCombinator::addNull($type)
					: $type;

				// the property's class/visibility/assign context is lexical, so it
				// comes from beforeScope; the scope-dependent receiver type is read
				// from the operand result above.
				$reflectionScope = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				$resolveProperty = function (string $propertyName) use ($nativeTypesPromoted, $reflectionScope, $receiverType, $expr): Type {
					if ($nativeTypesPromoted) {
						$propertyReflection = $reflectionScope->getInstancePropertyReflection($receiverType, $propertyName);
						if ($propertyReflection === null) {
							return new ErrorType();
						}

						if (!$propertyReflection->hasNativeType()) {
							return new MixedType();
						}

						return $propertyReflection->getNativeType();
					}

					return $this->propertyFetchType($reflectionScope, $receiverType, $propertyName, $expr) ?? new ErrorType();
				};

				if ($expr->name instanceof Identifier) {
					return $shortCircuit($resolveProperty($expr->name->toString()));
				}

				// dynamic property fetch $obj->$name: resolve each possible name
				// from beforeScope. The asking scope is not narrowed per name, so
				// $obj->{'foo'}-style fetches can be less precise. Every caller
				// walks a non-Identifier name and passes its result.
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
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypesWithNullsafeFan($expr, $context, $beforeScope, $nativeTypesPromoted),
		);
	}

	private function propertyFetchType(MutatingScope $scope, Type $fetchedOnType, string $propertyName, PropertyFetch $propertyFetch): ?Type
	{
		$propertyReflection = $scope->getInstancePropertyReflection($fetchedOnType, $propertyName);
		if ($propertyReflection === null) {
			return null;
		}

		if ($scope->isInWriteExpressionAssign($propertyFetch)) {
			return $propertyReflection->getWritableType();
		}

		return $propertyReflection->getReadableType();
	}

}
