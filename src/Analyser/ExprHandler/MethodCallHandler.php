<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper;
use PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_map;
use function array_merge;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements ExprHandler<MethodCall>
 */
#[AutowiredService]
final class MethodCallHandler implements ExprHandler
{

	public function __construct(
		private MethodCallReturnTypeHelper $methodCallReturnTypeHelper,
		private MethodThrowPointHelper $methodThrowPointHelper,
		private ReflectionProvider $reflectionProvider,
		private TypeSpecifier $typeSpecifier,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof MethodCall && !$expr->isFirstClassCallable();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$originalScope = $scope;
		if (
			($expr->var instanceof Expr\Closure || $expr->var instanceof Expr\ArrowFunction)
			&& $expr->name instanceof Identifier
			&& strtolower($expr->name->name) === 'call'
			&& isset($expr->getArgs()[0])
		) {
			$closureCallScope = $scope->enterClosureCall(
				$scope->getType($expr->getArgs()[0]->value),
				$scope->getNativeType($expr->getArgs()[0]->value),
			);
		}

		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $closureCallScope ?? $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $varResult->getScope();
		if (isset($closureCallScope)) {
			$scope = $scope->restoreOriginalScopeAfterClosureBind($originalScope);
		}

		return $this->processCallWithVarResult($nodeScopeResolver, $stmt, $expr, $varResult, $varResult->getType(), $scope, $storage, $nodeCallback, $context);
	}

	/**
	 * The call part after the var was evaluated — NullsafeMethodCallHandler
	 * reuses it with the subject narrowed non-null and the null stripped from
	 * $calledOnType, avoiding a second evaluation of the var.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processCallWithVarResult(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, MethodCall $expr, ExpressionResult $varResult, Type $calledOnType, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = $varResult->hasYield();
		$throwPoints = $varResult->getThrowPoints();
		$impurePoints = $varResult->getImpurePoints();
		$isAlwaysTerminating = $varResult->isAlwaysTerminating();
		$parametersAcceptor = null;
		$methodReflection = null;
		if ($expr->name instanceof Identifier) {
			$methodName = $expr->name->name;
			$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if ($methodReflection !== null) {
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
					$scope,
					$expr->getArgs(),
					$methodReflection->getVariants(),
					$methodReflection->getNamedArgumentsVariants(),
				);

			}
		} else {
			$methodNameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = array_merge($throwPoints, $methodNameResult->getThrowPoints());
			$scope = $methodNameResult->getScope();
		}

		if ($methodReflection !== null) {
			$impurePoint = SimpleImpurePoint::createFromVariant($methodReflection, $parametersAcceptor, $scope, $expr->getArgs());
			if ($impurePoint !== null) {
				$impurePoints[] = new ImpurePoint($scope, $expr, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain());
			}
		} else {
			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'methodCall',
				'call to unknown method',
				false,
			);
		}

		$normalizedExpr = $expr;
		if ($parametersAcceptor !== null) {
			$normalizedExpr = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $expr) ?? $expr;
			$returnType = $parametersAcceptor->getReturnType();
			$isAlwaysTerminating = $isAlwaysTerminating || ($returnType instanceof NeverType && $returnType->isExplicit());
		}

		$argsResult = $nodeScopeResolver->processArgs(
			$stmt,
			$methodReflection,
			$methodReflection !== null ? $scope->getNakedMethod($calledOnType, $methodReflection->getName()) : null,
			$parametersAcceptor,
			$normalizedExpr,
			$scope,
			$storage,
			$nodeCallback,
			$context,
		);
		$scope = $argsResult->getScope();

		if ($methodReflection !== null) {
			$throwPointScope = $scope;
			$methodThrowPoint = $this->methodThrowPointHelper->getThrowPoint(
				$methodReflection,
				$parametersAcceptor,
				$normalizedExpr,
				$scope,
				$context,
				fn (): Type => $this->resolveMethodCallTypeViaResults($normalizedExpr, $throwPointScope, $varResult, $argsResult->getCompanionResults(), $nodeScopeResolver, $stmt),
			);
			if ($methodThrowPoint !== null) {
				$throwPoints[] = $methodThrowPoint;
			}

			if ($methodReflection->getName() === '__construct' || $methodReflection->hasSideEffects()->yes()) {
				$nodeScopeResolver->callNodeCallback($nodeCallback, new InvalidateExprNode($normalizedExpr->var), $scope, $storage);
				$scope = $scope->invalidateExpression($normalizedExpr->var, true, $methodReflection->getDeclaringClass());
			} elseif ($this->rememberPossiblyImpureFunctionValues && $methodReflection->hasSideEffects()->maybe() && !$methodReflection->getDeclaringClass()->isBuiltin()) {
				$scope = $scope->assignExpression(
					new PossiblyImpureCallExpr($normalizedExpr, $normalizedExpr->var, sprintf('%s::%s()', $methodReflection->getDeclaringClass()->getDisplayName(), $methodReflection->getName())),
					$parametersAcceptor->getReturnType(),
					new MixedType(),
				);
			}
			if (!$methodReflection->isStatic()) {
				$selfOutType = $methodReflection->getSelfOutType();
				if ($selfOutType !== null) {
					$scope = $scope->assignExpression(
						$normalizedExpr->var,
						TemplateTypeHelper::resolveTemplateTypes(
							$selfOutType,
							$parametersAcceptor->getResolvedTemplateTypeMap(),
							$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
							TemplateTypeVariance::createCovariant(),
						),
						$scope->getNativeType($normalizedExpr->var),
					);
				}
			}

		} else {
			$nodeScopeResolver->callNodeCallback($nodeCallback, new InvalidateExprNode($normalizedExpr->var), $scope, $storage);
			$scope = $scope->invalidateExpression($normalizedExpr->var, true);
			$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
		}
		$hasYield = $hasYield || $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		$argResults = $argsResult->getCompanionResults();
		$typeCallback = function (Expr $e, MutatingScope $s) use ($varResult, $argResults, $nodeScopeResolver, $stmt): Type {
			if (!$e instanceof MethodCall) {
				throw new ShouldNotHappenException();
			}

			return $this->resolveMethodCallTypeViaResults($e, $s, $varResult, $argResults, $nodeScopeResolver, $stmt);
		};
		// the old specifyTypes() body stays the single source (the BinaryOp
		// precedent) — extensions, conditional return types and asserts resolve
		// through an unseeded adapter
		$specifyTypesCallback = function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt): SpecifiedTypes {
			if (!$e instanceof MethodCall) {
				throw new ShouldNotHappenException();
			}

			$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

			return $this->specifyTypes($this->typeSpecifier, $adapterScope, $e, $ctx);
		};

		$result = new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
		);

		if (!$expr->name instanceof Identifier) {
			return $result;
		}
		$methodName = $expr->name->name;
		$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
		if ($methodReflection === null) {
			return $result;
		}
		if (
			$scope->isInClass()
			&& $scope->getClassReflection()->getName() === $methodReflection->getDeclaringClass()->getName()
			&& ($scope->getFunctionName() !== null && strtolower($scope->getFunctionName()) === '__construct')
			&& TypeUtils::findThisType($calledOnType) !== null
		) {
			$calledMethodScope = $nodeScopeResolver->processCalledMethod($methodReflection);
			if ($calledMethodScope !== null) {
				$scope = $scope->mergeInitializedProperties($calledMethodScope);
				return new ExpressionResult(
					$scope,
					hasYield: $result->hasYield(),
					isAlwaysTerminating: $result->isAlwaysTerminating(),
					throwPoints: $result->getThrowPoints(),
					impurePoints: $result->getImpurePoints(),
					expr: $expr,
					typeCallback: $typeCallback,
					specifyTypesCallback: $specifyTypesCallback,
				);
			}
		}

		return $result;
	}

	/**
	 * New-world copy of resolveType(): the receiver comes from its
	 * ExpressionResult (one-level nullsafe short-circuit per NEW_WORLD.md
	 * paragraph 3.10); args and dynamic-return extensions resolve through a
	 * self-seeded adapter so extension helpers re-asking about this call
	 * terminate (the FuncCall precedent); dynamic method names bridge.
	 */
/**
	 * @param array<string, ExpressionResult> $argResults
	 */
	private function resolveMethodCallTypeViaResults(MethodCall $e, MutatingScope $s, ExpressionResult $varResult, array $argResults, NodeScopeResolver $nodeScopeResolver, Stmt $stmt): Type
	{
		if (!$e->name instanceof Identifier) {
			// dynamic method names take the guarded legacy bridge (PHPSTAN_FNSR=0)
			return $s->getType($e);
		}

		$varType = $varResult->getTypeForScope($s);
		$isShortcircuited = ($e->var instanceof Expr\NullsafePropertyFetch || $e->var instanceof Expr\NullsafeMethodCall)
			&& TypeCombinator::containsNull($varType);
		if ($isShortcircuited) {
			$varType = TypeCombinator::removeNull($varType);
		}

		if ($s->nativeTypesPromoted) {
			$methodReflection = $s->getMethodReflection($varType, $e->name->name);
			if ($methodReflection === null) {
				$returnType = new ErrorType();
			} else {
				$returnType = ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType();
			}

			return $isShortcircuited ? TypeCombinator::union($returnType, new NullType()) : $returnType;
		}

		$storage = new ExpressionResultStorage();
		$selfResult = new ExpressionResult(
			$s,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			expr: $e,
			typeCallback: fn (Expr $innerExpr, MutatingScope $innerScope): Type => $this->resolveMethodCallTypeViaResults($e, $innerScope, $varResult, $argResults, $nodeScopeResolver, $stmt),
			specifyTypesCallback: function (Expr $innerExpr, MutatingScope $innerScope, TypeSpecifierContext $innerContext) use ($nodeScopeResolver, $stmt): SpecifiedTypes {
				if (!$innerExpr instanceof MethodCall) {
					throw new ShouldNotHappenException();
				}

				$innerAdapterScope = $innerScope->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

				return $this->specifyTypes($this->typeSpecifier, $innerAdapterScope, $innerExpr, $innerContext);
			},
		);
		$adapterScope = $s->toResultAwareScope($argResults + [$s->getNodeKey($e) => $selfResult], $nodeScopeResolver, $stmt, $storage);

		$returnType = $this->methodCallReturnTypeHelper->methodCallReturnType(
			$adapterScope,
			$varType,
			$e->name->name,
			$e,
		);
		if ($returnType === null) {
			$returnType = new ErrorType();
		}

		return $isShortcircuited ? TypeCombinator::union($returnType, new NullType()) : $returnType;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->name instanceof Identifier) {
			if ($scope->nativeTypesPromoted) {
				$methodReflection = $scope->getMethodReflection(
					$scope->getNativeType($expr->var),
					$expr->name->name,
				);
				if ($methodReflection === null) {
					$returnType = new ErrorType();
				} else {
					$returnType = ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType();
				}

				return NullsafeShortCircuitingHelper::getType($scope, $expr->var, $returnType);
			}

			$returnType = $this->methodCallReturnTypeHelper->methodCallReturnType(
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
					->filterByTruthyValue(new Identical($expr->name, new String_($constantString->getValue())))
					->getType(new MethodCall($expr->var, new Identifier($constantString->getValue()), $expr->args)), $nameType->getConstantStrings()),
			);
		}

		return new MixedType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$expr->name instanceof Identifier) {
			return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
		}

		$methodCalledOnType = $scope->getType($expr->var);
		$methodReflection = $scope->getMethodReflection($methodCalledOnType, $expr->name->name);
		if ($methodReflection !== null) {
			// lazy create parametersAcceptor, as creation can be expensive
			$parametersAcceptor = null;

			$normalizedExpr = $expr;
			$args = $expr->getArgs();
			if (count($args) > 0) {
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $methodReflection->getVariants(), $methodReflection->getNamedArgumentsVariants());
				$normalizedExpr = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $expr) ?? $expr;
			}

			$referencedClasses = $methodCalledOnType->getObjectClassNames();
			if (
				count($referencedClasses) === 1
				&& $this->reflectionProvider->hasClass($referencedClasses[0])
			) {
				$methodClassReflection = $this->reflectionProvider->getClass($referencedClasses[0]);
				foreach ($typeSpecifier->getMethodTypeSpecifyingExtensionsForClass($methodClassReflection->getName()) as $extension) {
					if (!$extension->isMethodSupported($methodReflection, $normalizedExpr, $context)) {
						continue;
					}

					return $extension->specifyTypes($methodReflection, $normalizedExpr, $scope, $context);
				}
			}

			if (count($args) > 0) {
				$specifiedTypes = $typeSpecifier->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}
			}

			$assertions = $methodReflection->getAsserts();
			if ($assertions->getAll() !== []) {
				$parametersAcceptor ??= ParametersAcceptorSelector::selectFromArgs($scope, $args, $methodReflection->getVariants(), $methodReflection->getNamedArgumentsVariants());

				$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
					$type,
					$parametersAcceptor->getResolvedTemplateTypeMap(),
					$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					TemplateTypeVariance::createInvariant(),
				));
				$specifiedTypes = $typeSpecifier->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}
			}
		}

		return $typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
	}

}
