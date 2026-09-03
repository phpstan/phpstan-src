<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ArgsResult;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\DynamicReturnTypeStoragePrimer;
use PHPStan\Analyser\ExprHandler\Helper\EarlyTerminatingCallHelper;
use PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper;
use PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
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
		private MethodCallReturnTypeHelper $methodCallReturnTypeHelper,
		private MethodThrowPointHelper $methodThrowPointHelper,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
		private ExpressionResultFactory $expressionResultFactory,
		private TypeSpecifier $typeSpecifier,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private DynamicReturnTypeStoragePrimer $storagePrimer,
		private EarlyTerminatingCallHelper $earlyTerminatingHelper,
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
		$classResult = null;
		$nameResult = null;
		if ($expr->class instanceof Expr) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $classResult->hasYield();
			$throwPoints = array_merge($throwPoints, $classResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $classResult->getImpurePoints());
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();

			$scope = $classResult->getScope();
			$containsNullsafe = $classResult->containsNullsafe();
		}

		// A static call configured as early-terminating never returns: give it an
		// explicit never so the statement's exit point follows from the result type,
		// instead of NodeScopeResolver re-deriving it via Scope::getType().
		$isEarlyTerminating = false;
		if ($expr->name instanceof Identifier) {
			$earlyTerminatingClassType = $expr->class instanceof Name
				? $scope->resolveTypeByName($expr->class)
				: $classResult->getType();
			$isEarlyTerminating = $this->earlyTerminatingHelper->isEarlyTerminatingMethodCall($expr->name->name, $earlyTerminatingClassType);
		}
		$isAlwaysTerminating = $isAlwaysTerminating || $isEarlyTerminating;

		$parametersAcceptor = null;
		$variants = [];
		$namedArgumentsVariants = null;
		$methodReflection = null;
		$closureBindScopeFactory = null;
		if ($expr->name instanceof Identifier) {
			if ($expr->class instanceof Name) {
				// the acceptor selected here feeds the call's return type - a
				// STATIC method called through an explicit class name binds
				// `static` to that class, so select from the demoted type
				$classType = $this->resolveTypeByNameWithLateStaticBinding($scope, $expr->class, $expr->name->name);
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
						$closureBindScopeFactory = static function (MutatingScope $boundScope) use ($expr, $storage): MutatingScope {
							// invoked while the closure argument is walked; the other
							// arguments were processed before it (processArgs orders
							// closures last), so their results are already stored. A
							// missing result means degenerate code (a closure passed
							// where the bound $this/scope belongs) - price it as mixed
							// instead of walking on demand.
							$readArgType = static function (Expr $argValue, bool $useNativeTypes) use ($boundScope, $storage): Type {
								$argResult = $storage->findExpressionResult($argValue);
								return $argResult !== null
									? $argResult->getTypeOnScope($boundScope, $useNativeTypes)
									: new MixedType();
							};
							$thisType = null;
							$nativeThisType = null;
							if (isset($expr->getArgs()[1])) {
								$argType = $readArgType($expr->getArgs()[1]->value, false);
								if ($argType->isNull()->yes()) {
									$thisType = null;
								} else {
									$thisType = $argType;
								}

								$nativeArgType = $readArgType($expr->getArgs()[1]->value, true);
								if ($nativeArgType->isNull()->yes()) {
									$nativeThisType = null;
								} else {
									$nativeThisType = $nativeArgType;
								}
							}
							$scopeClasses = ['static'];
							if (isset($expr->getArgs()[2])) {
								$argValue = $expr->getArgs()[2]->value;
								$argValueType = $readArgType($argValue, false);

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
				// the class expr was processed above as the receiver; read its
				// already-computed result instead of re-walking via Scope::getType().
				// A nullsafe receiver's null is the chain short-circuit, not a
				// callee - strip it before the reflection lookup, like the
				// return-type resolution does.
				$classType = TypeCombinator::removeNull($classResult->getType())->getObjectTypeOrClassStringObjectType();
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
			// the class expr was processed above as the receiver; read its
			// already-computed result instead of re-walking via Scope::getType().
			$objectClasses = $classResult->getType()->getObjectClassNames();
			if (count($objectClasses) !== 1) {
				// the receiver may be a class-string instead of an object - the
				// instantiated type is what `new` would produce, read from the
				// same result instead of walking a synthetic New_ node
				$objectClasses = $classResult->getType()->getObjectTypeOrClassStringObjectType()->getObjectClassNames();
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

		$normalizedExpr = $expr;
		if ($parametersAcceptor !== null) {
			$normalizedExpr = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $expr) ?? $expr;
			$returnType = $parametersAcceptor->getReturnType();
			$isAlwaysTerminating = $isAlwaysTerminating || ($returnType instanceof NeverType && $returnType->isExplicit());
		}
		$scopeBeforeArgs = $scope;
		if ($parametersAcceptor !== null && $context->getInAssignRightSideExpr() === $expr) {
			$context = $context->enterAssignRightSideCallArgs($parametersAcceptor);
		}
		$argsResult = $nodeScopeResolver->processArgs($stmt, $methodReflection, null, $variants, $namedArgumentsVariants, $normalizedExpr, $scope, $storage, $nodeCallback, $context, $closureBindScopeFactory);
		$resolvedParametersAcceptor = $argsResult->getResolvedParametersAcceptor();
		$scope = $argsResult->getScope();
		$nodeScopeResolver->processDroppedArgs($stmt, $expr, $normalizedExpr, $scope, $storage, $context);

		if ($methodReflection !== null) {
			// created after the args were processed - the pure-unless-callable-
			// is-impure parameters read an argument's type, which is only
			// available once its result is stored
			$impurePoint = SimpleImpurePoint::createFromVariant($methodReflection, $parametersAcceptor, $scope, $expr->getArgs());
			if ($impurePoint !== null) {
				$impurePoints[] = new ImpurePoint($scopeBeforeArgs, $expr, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain());
			}
		} else {
			$impurePoints[] = new ImpurePoint(
				$scopeBeforeArgs,
				$expr,
				'methodCall',
				'call to unknown method',
				false,
			);
		}
		$scopeFunction = $scope->getFunction();

		// The early structural check above only sees the unresolved acceptor return
		// type; a conditional-return never (e.g. `($x is Foo ? never : string)`)
		// only resolves to never once the actual argument types are folded in by the
		// type-driven resolved acceptor.
		if ($resolvedParametersAcceptor !== null) {
			$resolvedReturnType = $resolvedParametersAcceptor->getReturnType();
			$isAlwaysTerminating = $isAlwaysTerminating || ($resolvedReturnType instanceof NeverType && $resolvedReturnType->isExplicit());
		}

		// The return type is derived from $resolvedParametersAcceptor - the acceptor
		// processArgs() selected from the arg types gathered on the arg-to-arg
		// evolving scope (type-driven, generics resolved). When null
		// (native-types-promoted, or on-demand / synthetic pricing) the acceptor is
		// re-derived from the already-processed argument results on the asking scope.
		$typeCallback = $isEarlyTerminating
			? static fn (bool $nativeTypesPromoted): Type => new NeverType(true)
			: fn (bool $nativeTypesPromoted): Type => $this->resolveReturnType(
				$nodeScopeResolver,
				$beforeScope,
				$nativeTypesPromoted,
				$expr,
				$classResult,
				$nameResult,
				$nativeTypesPromoted ? null : $resolvedParametersAcceptor,
				$argsResult,
			);
		$specifyTypesCallback = fn (TypeSpecifierContext $specifyContext, bool $nativeTypesPromoted): SpecifiedTypes => $this->specifyTypes(
			$nodeScopeResolver,
			$nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope,
			$expr,
			$normalizedExpr,
			$classResult,
			$resolvedParametersAcceptor,
			$specifyContext,
			$argsResult,
		);

		// A type constraint on a (narrowable, i.e. non-side-effecting) static call
		// narrows the call itself - the inside-out equivalent of createForExpr's
		// StaticCall purity gate + tail entry. An impure call narrows to nothing.
		$createTypesCallback = function (Type $type, TypeSpecifierContext $createContext, bool $nativeTypesPromoted) use ($expr, $classResult, $nodeScopeResolver, $beforeScope): SpecifiedTypes {
			$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;

			return $this->isStaticCallNarrowable($s, $expr, $classResult, $nodeScopeResolver)
				? $this->defaultNarrowingHelper->createSubjectTypes($s, $expr, null, $type, $createContext)
				: new SpecifiedTypes([], []);
		};

		// Store a preliminary result carrying the type/specify callbacks before the
		// throw point is computed: the method throw point resolves the return type
		// (resolveReturnType below) through dynamic static-method return type
		// extensions, which can narrow this very call on demand. Without a stored
		// result that narrowing would re-process this StaticCall on demand and
		// recurse. The callbacks are scope-independent, so the preliminary result
		// answers those asks correctly; finalize() below completes it with the
		// resolved scope and throw/impure points.
		$preliminaryResult = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: [],
			impurePoints: [],
			containsNullsafe: $containsNullsafe,
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
			createTypesCallback: $createTypesCallback,
			argsResult: $argsResult,
		);
		$nodeScopeResolver->storeExpressionResult($storage, $expr, $preliminaryResult);

		if ($methodReflection !== null) {
			// The call's return type, computed from the already-processed argument
			// results (resolveReturnType reads them via the class/name results,
			// never re-running processArgs) - asking
			// Scope::getType() for the StaticCall here would re-enter this handler on
			// demand, as its final result is not stored yet.
			// Resolve it through the stored preliminary result so the memoized
			// value seeds the final result below - the first later type read
			// would otherwise run resolveReturnType() again.
			$staticCallReturnType = $preliminaryResult->getKeepVoidType(false);
			$methodThrowPoint = $this->methodThrowPointHelper->getThrowPoint($methodReflection, $parametersAcceptor, $normalizedExpr, $scope, $context, $staticCallReturnType);
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
				TemplateArgumentFrame::returnTypeOfCall($acceptorForGenerics, $scope, $expr),
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
			$thisType = $scope->getVariableType('this');
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

		return $preliminaryResult->finalize($scope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
	}

	/**
	 * The call-expression type is derived from $preResolvedAcceptor - the acceptor
	 * processArgs() selected from the arg types gathered on the arg-to-arg evolving
	 * scope (type-driven, generics resolved). When null (native-types-promoted, or
	 * on-demand / synthetic pricing) it falls back to re-selecting from the args via
	 * MethodCallReturnTypeHelper on the asking scope.
	 *
	 * The class/name were processed during processExpr; their already computed
	 * results are read instead of re-walking via Scope::getType(). The dynamic-name
	 * branch builds a synthetic StaticCall priced on demand by the resolver.
	 *
	 */
	private function resolveReturnType(NodeScopeResolver $nodeScopeResolver, MutatingScope $reflectionScope, bool $nativeTypesPromoted, StaticCall $expr, ?ExpressionResult $classResult, ?ExpressionResult $nameResult, ?ParametersAcceptor $preResolvedAcceptor, ?ArgsResult $argsResult): Type
	{
		$classType = $classResult !== null
			? ($nativeTypesPromoted ? $classResult->getNativeType() : $classResult->getType())
			: null;
		// a call on a nullsafe chain whose class-receiver is currently nullable
		// short-circuits to null - the class result carries whether the chain
		// contains a ?-> (a plain nullable receiver does not propagate).
		$shortCircuit = static fn (Type $type): Type => $expr->class instanceof Expr
			&& $classResult !== null
			&& $classResult->containsNullsafe()
			&& $classType !== null
			&& TypeCombinator::containsNull($classType)
			? TypeCombinator::addNull($type)
			: $type;

		// the method reflection and dynamic-return-type extensions run on the
		// reflection scope (the lexical context / beforeScope); the class-
		// expression type is read from the operand result above.
		$resolveStaticMethod = function (string $methodName, StaticCall $staticCall) use ($reflectionScope, $nativeTypesPromoted, $classType, $expr, $preResolvedAcceptor, $argsResult): Type {
			if ($nativeTypesPromoted) {
				if ($expr->class instanceof Name) {
					$staticMethodCalledOnType = $this->resolveTypeByNameWithLateStaticBinding($reflectionScope, $expr->class, $methodName);
				} else {
					if ($classType === null) {
						throw new ShouldNotHappenException();
					}
					$staticMethodCalledOnType = $classType;
				}
				$methodReflection = $reflectionScope->getMethodReflection($staticMethodCalledOnType, $methodName);
				if ($methodReflection === null) {
					return new ErrorType();
				}

				return ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType();
			}

			if ($expr->class instanceof Name) {
				$staticMethodCalledOnType = $this->resolveTypeByNameWithLateStaticBinding($reflectionScope, $expr->class, $methodName);
			} else {
				if ($classType === null) {
					throw new ShouldNotHappenException();
				}
				$staticMethodCalledOnType = TypeCombinator::removeNull($classType)->getObjectTypeOrClassStringObjectType();
			}

			return $this->methodCallReturnTypeHelper->methodCallReturnType(
				$reflectionScope,
				$staticMethodCalledOnType,
				$methodName,
				$staticCall,
				$preResolvedAcceptor,
				$argsResult,
			) ?? new ErrorType();
		};

		if ($expr->name instanceof Identifier) {
			return $shortCircuit($resolveStaticMethod($expr->name->toString(), $expr));
		}

		// dynamic static call Foo::{$name}(): resolve each possible name on the
		// reflection scope. The asking scope is not narrowed per name, so such
		// calls can be less precise.
		if ($nameResult === null) {
			throw new ShouldNotHappenException();
		}

		$nameType = $nativeTypesPromoted ? $nameResult->getNativeType() : $nameResult->getType();
		if (count($nameType->getConstantStrings()) > 0) {
			return TypeCombinator::union(
				...array_map(static function ($constantString) use ($expr, $resolveStaticMethod): Type {
					if ($constantString->getValue() === '') {
						return new ErrorType();
					}

					return $resolveStaticMethod(
						$constantString->getValue(),
						new StaticCall($expr->class, new Identifier($constantString->getValue()), $expr->args),
					);
				}, $nameType->getConstantStrings()),
			);
		}

		return new MixedType();
	}

	/**
	 * Ported inside-out from the old TypeResolvingExprHandler::specifyTypes(): the
	 * StaticMethodTypeSpecifyingExtensions, conditional-return-type and assert
	 * narrowing are invoked on the already-processed argument
	 * results. The acceptor is $resolvedParametersAcceptor (type-driven, generics
	 * resolved by processArgs) rather than re-selected from the args on the asking
	 * scope. The subject's own default narrowing comes from DefaultNarrowingHelper
	 * instead of TypeSpecifier::handleDefaultTruthyOrFalseyContext(), which would
	 * re-enter this expression through TypeSpecifier::create().
	 *
	 * @param StaticCall $expr
	 * @param StaticCall $normalizedExpr
	 */
	private function specifyTypes(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, Expr $expr, Expr $normalizedExpr, ?ExpressionResult $classResult, ?ParametersAcceptor $resolvedParametersAcceptor, TypeSpecifierContext $context, ?ArgsResult $argsResult = null): SpecifiedTypes
	{
		if (!$expr->name instanceof Identifier) {
			return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
		}

		if ($expr->class instanceof Name) {
			$calleeType = $scope->resolveTypeByName($expr->class);
		} else {
			// the class expr was processed during processExpr; its result is
			// always captured for an expression class
			if ($classResult === null) {
				throw new ShouldNotHappenException();
			}
			$calleeType = $classResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		}

		$staticMethodReflection = $scope->getMethodReflection($calleeType, $expr->name->name);
		if ($staticMethodReflection !== null) {
			$args = $expr->getArgs();

			$referencedClasses = $calleeType->getObjectClassNames();
			if (
				count($referencedClasses) === 1
				&& $this->reflectionProvider->hasClass($referencedClasses[0])
			) {
				$staticMethodClassReflection = $this->reflectionProvider->getClass($referencedClasses[0]);
				// runs lazily at narrowing-apply time - prime the storage with the
				// argument results, see MethodCallHandler::specifyTypes()
				$popPrimedStorage = $this->storagePrimer->pushPrimedStorage($scope, $argsResult);
				try {
					foreach ($this->typeSpecifier->getStaticMethodTypeSpecifyingExtensionsForClass($staticMethodClassReflection->getName()) as $extension) {
						if (!$extension->isStaticMethodSupported($staticMethodReflection, $normalizedExpr, $context)) {
							continue;
						}

						return $extension->specifyTypes($staticMethodReflection, $normalizedExpr, $scope, $context);
					}
				} finally {
					$popPrimedStorage();
				}
			}

			if (count($args) > 0 && $resolvedParametersAcceptor !== null) {
				$specifiedTypes = $this->defaultNarrowingHelper->specifyTypesFromConditionalReturnType($context, $expr, $resolvedParametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}
			}

			$assertions = $staticMethodReflection->getAsserts();
			if ($assertions->getAll() !== [] && $resolvedParametersAcceptor !== null) {
				$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
					$type,
					$resolvedParametersAcceptor->getResolvedTemplateTypeMap(),
					$resolvedParametersAcceptor instanceof ExtendedParametersAcceptor ? $resolvedParametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					TemplateTypeVariance::createInvariant(),
				));
				$specifiedTypes = $this->defaultNarrowingHelper->specifyTypesFromAsserts($context, $expr, $asserts, $resolvedParametersAcceptor, $scope);
				if ($specifiedTypes !== null) {
					return $specifiedTypes
						->unionWith($this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context))
						->setRootExpr($specifiedTypes->getRootExpr());
				}
			}
		}

		return $this->defaultStaticCallNarrowing($scope, $expr, $classResult, $nodeScopeResolver, $context);
	}

	/**
	 * The default truthy/falsey narrowing of the call expression itself, gated by
	 * the same purity check TypeSpecifier::create() applies: a static method with
	 * side effects (or an unknown method whose result is not remembered) is not
	 * narrowable - calling it twice may yield different values - so it contributes
	 * no entry. Mirrors create()'s StaticCall handling inside-out, without
	 * re-entering this expression through create().
	 *
	 * @param StaticCall $expr
	 */
	private function defaultStaticCallNarrowing(MutatingScope $scope, Expr $expr, ?ExpressionResult $classResult, NodeScopeResolver $nodeScopeResolver, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$this->isStaticCallNarrowable($scope, $expr, $classResult, $nodeScopeResolver)) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
	}

	/** @param StaticCall $expr */
	private function isStaticCallNarrowable(MutatingScope $scope, Expr $expr, ?ExpressionResult $classResult, NodeScopeResolver $nodeScopeResolver): bool
	{
		if (!$expr->name instanceof Identifier) {
			return true;
		}

		if ($expr->class instanceof Name) {
			$calleeType = $scope->resolveTypeByName($expr->class);
		} else {
			if ($classResult === null) {
				throw new ShouldNotHappenException();
			}
			$calleeType = $classResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		}

		$methodReflection = $scope->getMethodReflection($calleeType, $expr->name->toString());
		if ($methodReflection === null) {
			return false;
		}

		$hasSideEffects = $methodReflection->hasSideEffects();
		if ($hasSideEffects->yes()) {
			return false;
		}

		return $this->rememberPossiblyImpureFunctionValues || $hasSideEffects->no();
	}

	/**
	 * An explicit class name within the current hierarchy resolves to a
	 * StaticType, but calling a STATIC method through it binds `static` to the
	 * named class - demote to the plain object type so `A::retStatic()` is `A`,
	 * not `static(self)`. self/static/parent keep late static binding.
	 */
	private function resolveTypeByNameWithLateStaticBinding(MutatingScope $scope, Name $class, string $methodName): TypeWithClassName
	{
		$classType = $scope->resolveTypeByName($class);

		if (
			$classType instanceof StaticType
			&& !in_array($class->toLowerString(), ['self', 'static', 'parent'], true)
		) {
			$methodReflectionCandidate = $scope->getMethodReflection(
				$classType,
				$methodName,
			);
			if ($methodReflectionCandidate !== null && $methodReflectionCandidate->isStatic()) {
				$classType = $classType->getStaticObjectType();
			}
		}

		return $classType;
	}

}
