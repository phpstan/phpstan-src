<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\EarlyTerminatingCallHelper;
use PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper;
use PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use ReflectionProperty;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements ExprHandler<StaticCall>
 */
#[AutowiredService]
final class StaticCallHandler implements ExprHandler
{

	public function __construct(
		private EarlyTerminatingCallHelper $earlyTerminatingCallHelper,
		private MethodCallReturnTypeHelper $methodCallReturnTypeHelper,
		private MethodThrowPointHelper $methodThrowPointHelper,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof StaticCall && !$expr->isFirstClassCallable();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$containsNullsafe = false;
		if ($expr->class instanceof Expr) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $classResult->hasYield();
			$throwPoints = array_merge($throwPoints, $classResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $classResult->getImpurePoints());
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();

			$scope = $classResult->getScope();
			$containsNullsafe = $classResult->containsNullsafe();
		}

		$parametersAcceptor = null;
		$variants = [];
		$namedArgumentsVariants = null;
		$methodReflection = null;
		$closureBindScopeFactory = null;
		if ($expr->name instanceof Identifier) {
			if ($expr->class instanceof Name) {
				$classType = $scope->resolveTypeByName($expr->class);
				$methodName = $expr->name->name;
				if ($classType->hasMethod($methodName)->yes()) {
					$methodReflection = $classType->getMethod($methodName, $scope);
					$variants = $methodReflection->getVariants();
					$namedArgumentsVariants = $methodReflection->getNamedArgumentsVariants();
					// A structural acceptor (names/positions/variadic) drives argument
					// normalization, the impure point and the throw point - generics are
					// resolved type-driven by processArgs() into $resolvedParametersAcceptor.
					$parametersAcceptor = ParametersAcceptorSelector::combineVariantsForNormalization($expr->getArgs(), $variants, $namedArgumentsVariants);

					$declaringClass = $methodReflection->getDeclaringClass();
					if (
						$declaringClass->getName() === 'Closure'
						&& strtolower($methodName) === 'bind'
					) {
						// deferred until the closure argument is processed: with
						// closures processed last, the bound $this/scope arguments
						// are already evaluated on the scope the factory receives
						$closureBindScopeFactory = static function (MutatingScope $boundScope) use ($expr, $parametersAcceptor): MutatingScope {
							// normalized so that $newThis and $newScope are found at their
							// parameter positions even when the call names its arguments
							$expr = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $expr) ?? $expr;
							$thisType = null;
							$nativeThisType = null;
							if (isset($expr->getArgs()[1])) {
								$argType = $boundScope->getType($expr->getArgs()[1]->value);
								if ($argType->isNull()->yes()) {
									$thisType = null;
								} else {
									$thisType = $argType;
								}

								$nativeArgType = $boundScope->getNativeType($expr->getArgs()[1]->value);
								if ($nativeArgType->isNull()->yes()) {
									$nativeThisType = null;
								} else {
									$nativeThisType = $nativeArgType;
								}
							}
							$scopeClasses = ['static'];
							if (isset($expr->getArgs()[2])) {
								$argValue = $expr->getArgs()[2]->value;
								$argValueType = $boundScope->getType($argValue);

								$directClassNames = $argValueType->getObjectClassNames();
								if (count($directClassNames) > 0) {
									$scopeClasses = $directClassNames;
									$thisTypes = [];
									foreach ($directClassNames as $directClassName) {
										$thisTypes[] = new ObjectType($directClassName);
									}
									$thisType = TypeCombinator::union(...$thisTypes);
								} else {
									$thisType = $argValueType->getClassStringObjectType();
									$scopeClasses = $thisType->getObjectClassNames();
								}
							}
							return $boundScope->enterClosureBind($thisType, $nativeThisType, $scopeClasses);
						};
					}
				} else {
					$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
				}
			} elseif ($expr->class instanceof Expr) {
				$classType = $scope->getType($expr->class)->getObjectTypeOrClassStringObjectType();
				$methodName = $expr->name->name;
				$methodReflection = $scope->getMethodReflection($classType, $methodName);
				if ($methodReflection !== null) {
					$variants = $methodReflection->getVariants();
					$namedArgumentsVariants = $methodReflection->getNamedArgumentsVariants();
					$parametersAcceptor = ParametersAcceptorSelector::combineVariantsForNormalization($expr->getArgs(), $variants, $namedArgumentsVariants);
				}
			}
		} else {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$scope = $nameResult->getScope();
		}

		if ($expr->class instanceof Expr) {
			$objectClasses = $scope->getType($expr->class)->getObjectClassNames();
			if (count($objectClasses) !== 1) {
				$objectClasses = $scope->getType(new New_($expr->class))->getObjectClassNames();
			}
			if (count($objectClasses) === 1) {
				$objectExprResult = $nodeScopeResolver->processExprNode($stmt, new StaticCall(new Name($objectClasses[0]), $expr->name, []), $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
				$additionalThrowPoints = $objectExprResult->getThrowPoints();
			} else {
				$additionalThrowPoints = [InternalThrowPoint::createImplicit($scope, $expr)];
			}
			foreach ($additionalThrowPoints as $throwPoint) {
				$throwPoints[] = $throwPoint;
			}
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
			$normalizedExpr = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $expr) ?? $expr;
			$returnType = $parametersAcceptor->getReturnType();
			$isAlwaysTerminating = $isAlwaysTerminating || ($returnType instanceof NeverType && $returnType->isExplicit());
		}
		$argsResult = $nodeScopeResolver->processArgs($stmt, $methodReflection, null, $variants, $namedArgumentsVariants, $normalizedExpr, $scope, $storage, $nodeCallback, $context, $closureBindScopeFactory);
		$resolvedParametersAcceptor = $argsResult->getResolvedParametersAcceptor();
		$scope = $argsResult->getScope();
		$scopeFunction = $scope->getFunction();

		if ($methodReflection !== null) {
			// The early structural check above only sees the unresolved acceptor
			// return type; a conditional-return never (e.g. `($x is Foo ? never :
			// string)`) only resolves to never once the actual argument types are
			// folded in by the type-driven resolved acceptor.
			if ($resolvedParametersAcceptor !== null) {
				$resolvedReturnType = $resolvedParametersAcceptor->getReturnType();
				$isAlwaysTerminating = $isAlwaysTerminating || ($resolvedReturnType instanceof NeverType && $resolvedReturnType->isExplicit());
			}
			$methodThrowPoint = $this->methodThrowPointHelper->getThrowPoint($methodReflection, $parametersAcceptor, $normalizedExpr, $scope, $context);
			if ($methodThrowPoint !== null) {
				$throwPoints[] = $methodThrowPoint;
			}
		}

		if (
			$expr->class instanceof Name
			&& $methodReflection !== null
			&& (
				(
					!$methodReflection->isStatic()
					&& $methodReflection->getName() === '__construct'
				)
				|| $methodReflection->hasSideEffects()->yes()
			)
			&& $scope->isInClass()
			&& $scope->getClassReflection()->is($methodReflection->getDeclaringClass()->getName())
		) {
			$scope = $scope->invalidateExpression(new Variable('this'), true, $methodReflection->getDeclaringClass());
		} elseif (
			$expr->class instanceof Name
			&& $methodReflection !== null
			&& $this->rememberPossiblyImpureFunctionValues
			&& $scope->isInClass()
			&& $scope->getClassReflection()->is($methodReflection->getDeclaringClass()->getName())
			&& $methodReflection->hasSideEffects()->maybe()
			&& !$methodReflection->getDeclaringClass()->isBuiltin()
		) {
			// the remembered call value is generic-sensitive: resolve it from the
			// type-driven acceptor processArgs() selected (generics resolved against
			// the actual arg types), falling back to the structural acceptor.
			$acceptorForGenerics = $resolvedParametersAcceptor ?? $parametersAcceptor;
			$scope = $scope->assignExpression(
				new PossiblyImpureCallExpr($normalizedExpr, new Variable('this'), sprintf('%s::%s()', $methodReflection->getDeclaringClass()->getDisplayName(), $methodReflection->getName())),
				$acceptorForGenerics->getReturnType(),
				new MixedType(),
			);
		}

		if (
			$expr->class instanceof Name
			&& $methodReflection !== null
			&& !$methodReflection->isStatic()
			&& $methodReflection->getName() === '__construct'
			&& $scopeFunction instanceof MethodReflection
			&& !$scopeFunction->isStatic()
			&& $scope->isInClass()
			&& $scope->getClassReflection()->isSubclassOfClass($methodReflection->getDeclaringClass())
		) {
			$thisType = $scope->getType(new Variable('this'));
			$methodClassReflection = $methodReflection->getDeclaringClass();
			foreach ($methodClassReflection->getNativeReflection()->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $property) {
				if (!$property->isPromoted() || $property->getDeclaringClass()->getName() !== $methodClassReflection->getName()) {
					continue;
				}

				$scope = $scope->assignInitializedProperty($thisType, $property->getName());
			}
		}

		if (
			$methodReflection === null
			|| (!$methodReflection->getDeclaringClass()->isBuiltin() && !$methodReflection->hasSideEffects()->no())
		) {
			$scope = $scope->invalidateVolatileExpressions();
		}

		$hasYield = $hasYield || $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			containsNullsafe: $containsNullsafe,
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->name instanceof Identifier) {
			$earlyTerminatingClassType = $expr->class instanceof Name
				? $scope->resolveTypeByName($expr->class)
				: $scope->getType($expr->class);
			if ($this->earlyTerminatingCallHelper->isEarlyTerminatingMethodCall($expr->name->name, $earlyTerminatingClassType)) {
				return new NeverType(true);
			}
		}

		if ($expr->name instanceof Identifier) {
			if ($scope->nativeTypesPromoted) {
				if ($expr->class instanceof Name) {
					$staticMethodCalledOnType = $this->resolveTypeByNameWithLateStaticBinding($scope, $expr->class, $expr->name);
				} else {
					$staticMethodCalledOnType = $scope->getNativeType($expr->class);
				}
				$methodReflection = $scope->getMethodReflection(
					$staticMethodCalledOnType,
					$expr->name->name,
				);
				if ($methodReflection === null) {
					$callType = new ErrorType();
				} else {
					$callType = ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType();
				}

				if ($expr->class instanceof Expr) {
					return NullsafeShortCircuitingHelper::getType($scope, $expr->class, $callType);
				}

				return $callType;
			}

			if ($expr->class instanceof Name) {
				$staticMethodCalledOnType = $this->resolveTypeByNameWithLateStaticBinding($scope, $expr->class, $expr->name);
			} else {
				$staticMethodCalledOnType = TypeCombinator::removeNull($scope->getType($expr->class))->getObjectTypeOrClassStringObjectType();
			}

			$callType = $this->methodCallReturnTypeHelper->methodCallReturnType(
				$scope,
				$staticMethodCalledOnType,
				$expr->name->toString(),
				$expr,
			);
			if ($callType === null) {
				$callType = new ErrorType();
			}

			if ($expr->class instanceof Expr) {
				return NullsafeShortCircuitingHelper::getType($scope, $expr->class, $callType);
			}

			return $callType;
		}

		$nameType = $scope->getType($expr->name);
		if (count($nameType->getConstantStrings()) > 0) {
			return TypeCombinator::union(
				...array_map(static fn ($constantString) => $constantString->getValue() === '' ? new ErrorType() : $scope
					->filterByTruthyValue(new Identical($expr->name, new String_($constantString->getValue())))
					->getType(new Expr\StaticCall($expr->class, new Identifier($constantString->getValue()), $expr->args)), $nameType->getConstantStrings()),
			);
		}

		return new MixedType();
	}

	private function resolveTypeByNameWithLateStaticBinding(MutatingScope $scope, Name $class, Identifier $name): TypeWithClassName
	{
		$classType = $scope->resolveTypeByName($class);

		if (
			$classType instanceof StaticType
			&& !in_array($class->toLowerString(), ['self', 'static', 'parent'], true)
		) {
			$methodReflectionCandidate = $scope->getMethodReflection(
				$classType,
				$name->name,
			);
			if ($methodReflectionCandidate !== null && $methodReflectionCandidate->isStatic()) {
				$classType = $classType->getStaticObjectType();
			}
		}

		return $classType;
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$expr->name instanceof Identifier) {
			return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
		}

		if ($expr->class instanceof Name) {
			$calleeType = $scope->resolveTypeByName($expr->class);
		} else {
			$calleeType = $scope->getType($expr->class);
		}

		$staticMethodReflection = $scope->getMethodReflection($calleeType, $expr->name->name);
		if ($staticMethodReflection !== null) {
			// lazy create parametersAcceptor, as creation can be expensive
			$parametersAcceptor = null;

			$normalizedExpr = $expr;
			$args = $expr->getArgs();
			if (count($args) > 0) {
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $staticMethodReflection->getVariants(), $staticMethodReflection->getNamedArgumentsVariants());
				$normalizedExpr = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $expr) ?? $expr;
			}

			$referencedClasses = $calleeType->getObjectClassNames();
			if (
				count($referencedClasses) === 1
				&& $this->reflectionProvider->hasClass($referencedClasses[0])
			) {
				$staticMethodClassReflection = $this->reflectionProvider->getClass($referencedClasses[0]);
				foreach ($typeSpecifier->getStaticMethodTypeSpecifyingExtensionsForClass($staticMethodClassReflection->getName()) as $extension) {
					if (!$extension->isStaticMethodSupported($staticMethodReflection, $normalizedExpr, $context)) {
						continue;
					}

					return $extension->specifyTypes($staticMethodReflection, $normalizedExpr, $scope, $context);
				}
			}

			if (count($args) > 0) {
				$specifiedTypes = $typeSpecifier->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}
			}

			$assertions = $staticMethodReflection->getAsserts();
			if ($assertions->getAll() !== []) {
				$parametersAcceptor ??= ParametersAcceptorSelector::selectFromArgs($scope, $args, $staticMethodReflection->getVariants(), $staticMethodReflection->getNamedArgumentsVariants());

				$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
					$type,
					$parametersAcceptor->getResolvedTemplateTypeMap(),
					$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					TemplateTypeVariance::createInvariant(),
				));
				$specifiedTypes = $typeSpecifier->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes
						->unionWith($typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope))
						->setRootExpr($specifiedTypes->getRootExpr());
				}
			}
		}

		return $typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
	}

}
