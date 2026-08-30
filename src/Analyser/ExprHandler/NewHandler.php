<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
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
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\Analyser\Traverser\ConstructorClassTemplateTraverser;
use PHPStan\Analyser\Traverser\GenericTypeTemplateTraverser;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Parser\NewAssignedToPropertyVisitor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function sprintf;

/**
 * @implements ExprHandler<New_>
 */
#[AutowiredService]
final class NewHandler implements ExprHandler
{

	/**
	 * @param ExtensionsCollection<DynamicStaticMethodThrowTypeExtension> $dynamicStaticMethodThrowTypeExtensions
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		#[AutowiredExtensions(of: DynamicStaticMethodThrowTypeExtension::class)]
		private ExtensionsCollection $dynamicStaticMethodThrowTypeExtensions,
		private DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry,
		private PropertyReflectionFinder $propertyReflectionFinder,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private DynamicReturnTypeStoragePrimer $storagePrimer,
		private Container $container,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof New_ && !$expr->isFirstClassCallable();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$parametersAcceptor = null;
		$constructorReflection = null;
		$classReflection = null;
		$isDynamic = false;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$normalizedExpr = $expr;
		$className = null;
		$classResult = null;
		$deferredConstructorImpureIsDynamic = null;
		if ($expr->class instanceof Name) {
			$className = $scope->resolveName($expr->class);

			[$constructorReflection, $classReflection, $parametersAcceptor] = $this->processConstructorReflection($className, $expr);
			$deferredConstructorImpureIsDynamic = false;

			if ($parametersAcceptor !== null) {
				$normalizedExpr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
			}

		} elseif ($expr->class instanceof Node\Stmt\Class_) {
			$classReflection = $this->reflectionProvider->getAnonymousClassReflection($expr->class, $scope); // populates $expr->class->name
			if ($classReflection->hasConstructor()) {
				$constructorReflection = $classReflection->getConstructor();
				// A structural acceptor (names/positions/variadic) drives argument
				// normalization and the throw point - generics are resolved
				// type-driven by processArgs() into $resolvedParametersAcceptor.
				$parametersAcceptor = ParametersAcceptorSelector::combineVariantsForNormalization($expr->getArgs(), $constructorReflection->getVariants(), $constructorReflection->getNamedArgumentsVariants());

				if ($constructorReflection->getDeclaringClass()->getName() === $classReflection->getName()) {
					$constructorResult = null;
					$nodeScopeResolver->pushNodeGatherer(static function (Node $node, Scope $scope) use ($classReflection, &$constructorResult): void {
						if (!$node instanceof MethodReturnStatementsNode) {
							return;
						}
						if ($constructorResult !== null) {
							return;
						}
						$currentClassReflection = $node->getClassReflection();
						if ($currentClassReflection->getName() !== $classReflection->getName()) {
							return;
						}
						if (!$currentClassReflection->hasConstructor()) {
							return;
						}
						if ($currentClassReflection->getConstructor()->getName() !== $node->getMethodReflection()->getName()) {
							return;
						}
						$constructorResult = $node;
					});
					try {
						$nodeScopeResolver->processStmtNode($expr->class, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
					} finally {
						$nodeScopeResolver->popNodeGatherer();
					}

					if ($constructorResult !== null) {
						$throwPoints = array_map(static fn (ThrowPoint $point): InternalThrowPoint => InternalThrowPoint::createFromPublic($point, $scope), $constructorResult->getStatementResult()->getThrowPoints());
						$impurePoints = $constructorResult->getImpurePoints();
					}
				} else {
					$nodeScopeResolver->processStmtNode($expr->class, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
					if (!$constructorReflection->hasSideEffects()->no()) {
						$certain = $constructorReflection->isPure()->no();
						$impurePoints[] = new ImpurePoint(
							$scope,
							$expr,
							'new',
							sprintf('instantiation of class %s', $constructorReflection->getDeclaringClass()->getDisplayName()),
							$certain,
						);
					}
				}
			} else {
				$nodeScopeResolver->processStmtNode($expr->class, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
			}

			if ($parametersAcceptor !== null) {
				$normalizedExpr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
			}
		} else {
			$isDynamic = true;

			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $classResult->getScope();
			$hasYield = $classResult->hasYield();
			$throwPoints = $classResult->getThrowPoints();
			$impurePoints = $classResult->getImpurePoints();
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();

			// The instantiated object type derives from the class expression - read
			// its already-processed result rather than asking Scope::getType() for
			// the not-yet-stored New_ node, which would re-enter this handler.
			$objectClasses = $classResult->getType()->getObjectTypeOrClassStringObjectType()->getObjectClassNames();
			if (count($objectClasses) === 1) {
				$objectExprResult = $nodeScopeResolver->processExprNode($stmt, new New_(new Name($objectClasses[0])), $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
				$className = $objectClasses[0];
				$additionalThrowPoints = $objectExprResult->getThrowPoints();
			} else {
				$className = null;
				$additionalThrowPoints = [InternalThrowPoint::createImplicit($scope, $expr)];
			}

			$throwPoints = array_merge($throwPoints, $additionalThrowPoints);

			if ($className !== null) {
				[$constructorReflection, $classReflection, $parametersAcceptor] = $this->processConstructorReflection($className, $expr);
				$deferredConstructorImpureIsDynamic = true;
			} else {
				$impurePoints[] = new ImpurePoint(
					$scope,
					$expr,
					'new',
					'instantiation of unknown class',
					false,
				);
			}

			if ($parametersAcceptor !== null) {
				$normalizedExpr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
			}
		}

		$variants = $constructorReflection !== null ? $constructorReflection->getVariants() : [];
		$namedArgumentsVariants = $constructorReflection !== null ? $constructorReflection->getNamedArgumentsVariants() : null;
		$scopeBeforeArgs = $scope;
		$argsResult = $nodeScopeResolver->processArgs($stmt, $constructorReflection, null, $variants, $namedArgumentsVariants, $normalizedExpr, $scope, $storage, $nodeCallback, $context);
		$resolvedParametersAcceptor = $argsResult->getResolvedParametersAcceptor();
		$scope = $argsResult->getScope();
		$nodeScopeResolver->processDroppedArgs($stmt, $expr, $normalizedExpr, $scope, $storage, $context);
		$hasYield = $hasYield || $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		if ($deferredConstructorImpureIsDynamic !== null) {
			// created after the args were processed - the pure-unless-callable-
			// is-impure parameters read an argument's type, which is only
			// available once its result is stored
			$impurePoints = array_merge($impurePoints, $this->getConstructorImpurePoints($constructorReflection, $classReflection, $parametersAcceptor, $expr, $scope, $scopeBeforeArgs, $deferredConstructorImpureIsDynamic));
		}
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		// The new-expression type is derived from $resolvedParametersAcceptor - the
		// constructor acceptor processArgs() selected from the arg types gathered on
		// the arg-to-arg evolving scope (type-driven, resolves the class's @template
		// parameters from constructor args). When null (native-types-promoted, or
		// on-demand / synthetic pricing), resolveReturnType() re-selects a structural
		// acceptor from the args on the asking scope.
		$typeCallback = fn (bool $nativeTypesPromoted): Type => $this->resolveReturnType(
			$nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope,
			$expr,
			$nativeTypesPromoted ? null : $resolvedParametersAcceptor,
			$classResult !== null ? ($nativeTypesPromoted ? $classResult->getNativeType() : $classResult->getType()) : null,
			$argsResult,
		);
		$specifyTypesCallback = fn (TypeSpecifierContext $specifyContext, bool $nativeTypesPromoted): SpecifiedTypes => $this->specifyTypes(
			$nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope,
			$expr,
			$resolvedParametersAcceptor,
			$specifyContext,
		);

		// Store a preliminary result carrying the type/specify callbacks before the
		// throw-point return type is computed: getConstructorThrowPoint() and the
		// exact-instantiation return type resolution can re-enter on demand (e.g. a
		// dynamic static-method return type extension narrowing this very
		// instantiation). Without a stored result that narrowing would re-process
		// this New_ on demand and recurse. The callbacks are scope-independent, so
		// the preliminary result answers those asks correctly; the final result
		// finalize() completes it with the resolved scope and throw/impure points.
		$preliminaryResult = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: [],
			impurePoints: [],
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
		);
		$nodeScopeResolver->storeExpressionResult($storage, $expr, $preliminaryResult);

		if ($constructorReflection !== null && $parametersAcceptor !== null) {
			$className ??= $constructorReflection->getDeclaringClass()->getName();
			$constructorThrowPoint = $this->getConstructorThrowPoint($constructorReflection, $parametersAcceptor, $expr, new Name\FullyQualified($className), $expr->getArgs(), $scope, $context);
			if ($constructorThrowPoint !== null) {
				$throwPoints[] = $constructorThrowPoint;
			}
		} elseif ($classReflection === null || ($isDynamic && $constructorReflection === null && !$classReflection->isFinal())) {
			$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
		}

		$calleeKnown = $classReflection !== null && !($isDynamic && !$classReflection->isFinal());
		if (
			($constructorReflection !== null && !$constructorReflection->getDeclaringClass()->isBuiltin() && !$constructorReflection->hasSideEffects()->no())
			|| ($constructorReflection === null && !$calleeKnown)
		) {
			$scope = $scope->invalidateVolatileExpressions();
		}

		return $preliminaryResult->finalize($scope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
	}

	/**
	 * @return array{?ExtendedMethodReflection, ?ClassReflection, ?ParametersAcceptor}
	 */
	private function processConstructorReflection(string $className, New_ $expr): array
	{
		$constructorReflection = null;
		$parametersAcceptor = null;

		$classReflection = null;
		if ($this->reflectionProvider->hasClass($className)) {
			$classReflection = $this->reflectionProvider->getClass($className);
			if ($classReflection->hasConstructor()) {
				$constructorReflection = $classReflection->getConstructor();
				// A structural acceptor (names/positions/variadic) drives argument
				// normalization and the throw point - generics are resolved
				// type-driven by processArgs() into $resolvedParametersAcceptor.
				$parametersAcceptor = ParametersAcceptorSelector::combineVariantsForNormalization($expr->getArgs(), $constructorReflection->getVariants(), $constructorReflection->getNamedArgumentsVariants());
			}
		}

		return [$constructorReflection, $classReflection, $parametersAcceptor];
	}

	/**
	 * @return ImpurePoint[]
	 */
	private function getConstructorImpurePoints(?ExtendedMethodReflection $constructorReflection, ?ClassReflection $classReflection, ?ParametersAcceptor $parametersAcceptor, New_ $expr, MutatingScope $scope, MutatingScope $scopeBeforeArgs, bool $isDynamic): array
	{
		if ($constructorReflection !== null) {
			if ($parametersAcceptor === null) {
				throw new ShouldNotHappenException();
			}
			if ($constructorReflection->hasSideEffects()->no()) {
				return [];
			}

			$certain = $constructorReflection->isPure()->no();
			$verdict = SimpleImpurePoint::resolvePureUnlessCallableIsImpureVerdict($parametersAcceptor, $scope, $expr->getArgs());
			if ($verdict !== null && $verdict->yes()) {
				return [];
			}
			if ($verdict !== null && $verdict->no()) {
				$certain = true;
			}

			return [
				new ImpurePoint(
					$scopeBeforeArgs,
					$expr,
					'new',
					sprintf('instantiation of class %s', $constructorReflection->getDeclaringClass()->getDisplayName()),
					$certain,
				),
			];
		}

		if ($classReflection === null) {
			return [
				new ImpurePoint(
					$scopeBeforeArgs,
					$expr,
					'new',
					'instantiation of unknown class',
					false,
				),
			];
		}

		if ($isDynamic && !$classReflection->isFinal()) {
			return [
				new ImpurePoint(
					$scopeBeforeArgs,
					$expr,
					'new',
					sprintf('instantiation of class %s', $classReflection->getDisplayName()),
					false,
				),
			];
		}

		return [];
	}

	/**
	 * @param list<Node\Arg> $args
	 */
	private function getConstructorThrowPoint(MethodReflection $constructorReflection, ParametersAcceptor $parametersAcceptor, New_ $new, Name $className, array $args, MutatingScope $scope, ExpressionContext $context): ?InternalThrowPoint
	{
		$methodCall = new StaticCall($className, $constructorReflection->getName(), $args);
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicStaticMethodThrowTypeExtensions->getAll() as $extension) {
				if (!$extension->isStaticMethodSupported($constructorReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromStaticMethodCall($constructorReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $new, false);
			}
		}

		if ($constructorReflection->getThrowType() !== null) {
			$throwType = $constructorReflection->getThrowType();
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $new, true);
			}
		} elseif ($this->implicitThrows) {
			if (!$context->isInThrow() || !$constructorReflection->getDeclaringClass()->is(Throwable::class)) {
				return InternalThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
	}

	/**
	 * The stored new-expression type is derived from $preResolvedAcceptor - the
	 * constructor acceptor processArgs() selected from the arg types gathered on
	 * the arg-to-arg evolving scope (resolves the class's @template parameters
	 * from constructor args). Null falls back to re-selecting a structural acceptor
	 * from the args on the asking scope (on-demand / synthetic pricing).
	 *
	 * @param New_ $expr
	 */
	private function resolveReturnType(MutatingScope $scope, Expr $expr, ?ParametersAcceptor $preResolvedAcceptor, ?Type $classExprType, ?ArgsResult $argsResult = null): Type
	{
		if ($expr->class instanceof Name) {
			return $this->exactInstantiation($scope, $expr, $expr->class, $preResolvedAcceptor, $argsResult);
		}
		if ($expr->class instanceof Node\Stmt\Class_) {
			$anonymousClassReflection = $this->reflectionProvider->getAnonymousClassReflection($expr->class, $scope);

			return new ObjectType($anonymousClassReflection->getName());
		}

		// the class expression was walked by processExpr; its result's type of
		// the asked flavour is passed in by the typeCallback
		if ($classExprType === null) {
			throw new ShouldNotHappenException();
		}
		return $classExprType->getObjectTypeOrClassStringObjectType();
	}

	private function exactInstantiation(MutatingScope $scope, New_ $node, Name $className, ?ParametersAcceptor $preResolvedAcceptor, ?ArgsResult $argsResult = null): Type
	{
		$resolvedClassName = $scope->resolveName($className);
		$isStatic = false;
		$lowercasedClassName = $className->toLowerString();
		if ($lowercasedClassName === 'static') {
			$isStatic = true;
		}

		if (!$this->reflectionProvider->hasClass($resolvedClassName)) {
			if ($lowercasedClassName === 'static') {
				if (!$scope->isInClass()) {
					return new ErrorType();
				}

				return new StaticType($scope->getClassReflection());
			}
			if ($lowercasedClassName === 'parent') {
				return new NonexistentParentClassType();
			}

			return new ObjectType($resolvedClassName);
		}

		$classReflection = $this->reflectionProvider->getClass($resolvedClassName);
		$nonFinalClassReflection = $classReflection;
		if (!$isStatic) {
			$classReflection = $classReflection->asFinal();
		}
		if ($classReflection->hasConstructor()) {
			$constructorMethod = $classReflection->getConstructor();
		} else {
			$constructorMethod = new DummyConstructorReflection($classReflection);
		}

		if ($constructorMethod->getName() === '') {
			throw new ShouldNotHappenException();
		}

		$resolvedTypes = [];
		$methodCall = new Expr\StaticCall(
			new Name($resolvedClassName),
			new Node\Identifier($constructorMethod->getName()),
			$node->getArgs(),
		);

		$parametersAcceptor = $preResolvedAcceptor ?? ParametersAcceptorSelector::combineVariantsForNormalization(
			$methodCall->getArgs(),
			$constructorMethod->getVariants(),
			$constructorMethod->getNamedArgumentsVariants(),
		);
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);

		if ($normalizedMethodCall !== null) {
			// runs lazily in the typeCallback - prime the storage with the argument
			// results so the extensions' Scope::getType() asks about the arguments
			// answer from them instead of re-walking on demand
			$popPrimedStorage = $this->storagePrimer->pushPrimedStorage($scope, $argsResult);
			try {
				foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicStaticMethodReturnTypeExtensionsForClass($classReflection->getName()) as $dynamicStaticMethodReturnTypeExtension) {
					if (!$dynamicStaticMethodReturnTypeExtension->isStaticMethodSupported($constructorMethod)) {
						continue;
					}

					$resolvedType = $dynamicStaticMethodReturnTypeExtension->getTypeFromStaticMethodCall(
						$constructorMethod,
						$normalizedMethodCall,
						$scope,
					);
					if ($resolvedType === null) {
						continue;
					}

					$resolvedTypes[] = $resolvedType;
				}
			} finally {
				$popPrimedStorage();
			}
		}

		if (count($resolvedTypes) > 0) {
			return TypeCombinator::union(...$resolvedTypes);
		}

		// A constructor makes `new` never-returning only when its own return type
		// is (or can resolve to) explicit never; the dynamic static-method return
		// type extensions already ran above, so only the base return type is left
		// to check. Pricing the synthetic StaticCall on demand for this is
		// expensive and pointless for the overwhelmingly common plain
		// void/object constructor - skip it unless the return type could be never.
		$constructorReturnType = $parametersAcceptor->getReturnType();
		if ($constructorReturnType instanceof NeverType || $constructorReturnType->hasTemplateOrLateResolvableType()) {
			// $methodCall is a synthetic StaticCall the handler built; price it
			// through the sanctioned on-demand walk (the constructor's own
			// never-returning conditional return type).
			$methodResult = $this->container->getByType(NodeScopeResolver::class)->processSyntheticOnDemand($methodCall, $scope)->getTypeOnScope($scope, false);
			if ($methodResult instanceof NeverType && $methodResult->isExplicit()) {
				return $methodResult;
			}
		}

		$objectType = $isStatic ? new StaticType($classReflection) : new ObjectType($resolvedClassName, classReflection: $classReflection);
		if (!$classReflection->isGeneric()) {
			return $objectType;
		}

		$assignedToProperty = $node->getAttribute(NewAssignedToPropertyVisitor::ATTRIBUTE_NAME);
		if ($assignedToProperty !== null) {
			$constructorVariants = $constructorMethod->getVariants();
			if (count($constructorVariants) === 1) {
				$constructorVariant = $constructorVariants[0];
				$classTemplateTypes = $classReflection->getTemplateTypeMap()->getTypes();
				$originalClassTemplateTypes = $classTemplateTypes;

				$traverser = new ConstructorClassTemplateTraverser($classTemplateTypes);
				foreach ($constructorVariant->getParameters() as $parameter) {
					if (!$parameter->getType()->hasTemplateOrLateResolvableType()) {
						continue;
					}
					TypeTraverser::map($parameter->getType(), $traverser);
				}
				$classTemplateTypes = $traverser->getClassTemplateTypes();

				if (count($classTemplateTypes) === count($originalClassTemplateTypes)) {
					$foundProperty = $this->propertyReflectionFinder->findPropertyReflectionFromNode($assignedToProperty, $scope);
					if ($foundProperty !== null) {
						$nonFinalObjectType = $isStatic ? new StaticType($nonFinalClassReflection) : new ObjectType($resolvedClassName, classReflection: $nonFinalClassReflection);
						$propertyType = TypeCombinator::intersect($foundProperty->getWritableType(), $nonFinalObjectType);
						if (!$propertyType instanceof NeverType) {
							return $propertyType;
						}
					}
				}
			}
		}

		if ($constructorMethod instanceof DummyConstructorReflection) {
			if ($isStatic) {
				return new GenericStaticType(
					$classReflection,
					$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
					null,
					[],
				);
			}

			$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
			return new GenericObjectType(
				$resolvedClassName,
				$types,
				classReflection: $classReflection->withTypes($types)->asFinal(),
			);
		}

		if ($constructorMethod->getDeclaringClass()->getName() !== $classReflection->getName()) {
			if (!$constructorMethod->getDeclaringClass()->isGeneric()) {
				if ($isStatic) {
					return new GenericStaticType(
						$classReflection,
						$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
						null,
						[],
					);
				}

				$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
				return new GenericObjectType(
					$resolvedClassName,
					$types,
					classReflection: $classReflection->withTypes($types)->asFinal(),
				);
			}
			$newType = new GenericObjectType($resolvedClassName, $classReflection->typeMapToList($classReflection->getTemplateTypeMap()));
			$ancestorType = $newType->getAncestorWithClassName($constructorMethod->getDeclaringClass()->getName());
			if ($ancestorType === null) {
				if ($isStatic) {
					return new GenericStaticType(
						$classReflection,
						$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
						null,
						[],
					);
				}

				$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
				return new GenericObjectType(
					$resolvedClassName,
					$types,
					classReflection: $classReflection->withTypes($types)->asFinal(),
				);
			}
			$ancestorClassReflections = $ancestorType->getObjectClassReflections();
			if (count($ancestorClassReflections) !== 1) {
				if ($isStatic) {
					return new GenericStaticType(
						$classReflection,
						$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
						null,
						[],
					);
				}

				$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
				return new GenericObjectType(
					$resolvedClassName,
					$types,
					classReflection: $classReflection->withTypes($types)->asFinal(),
				);
			}

			$newParentNode = new New_(new Name($constructorMethod->getDeclaringClass()->getName()), $node->args);
			// the synthetic walk is load-bearing: it re-resolves the parent
			// constructor's template types from the arguments (processArgs against
			// the parent's signature), which a direct exactInstantiation() recursion
			// with the child's acceptor cannot do
			$newParentType = $this->container->getByType(NodeScopeResolver::class)->processSyntheticOnDemand($newParentNode, $scope)->getTypeOnScope($scope, false);
			$newParentTypeClassReflections = $newParentType->getObjectClassReflections();
			if (count($newParentTypeClassReflections) !== 1) {
				if ($isStatic) {
					return new GenericStaticType(
						$classReflection,
						$classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
						null,
						[],
					);
				}

				$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds());
				return new GenericObjectType(
					$resolvedClassName,
					$types,
					classReflection: $classReflection->withTypes($types)->asFinal(),
				);
			}
			$newParentTypeClassReflection = $newParentTypeClassReflections[0];

			$ancestorClassReflection = $ancestorClassReflections[0];
			$ancestorMapping = [];
			foreach ($ancestorClassReflection->getActiveTemplateTypeMap()->getTypes() as $typeName => $templateType) {
				if (!$templateType instanceof TemplateType) {
					continue;
				}

				$ancestorMapping[$typeName] = $templateType;
			}

			$resolvedTypeMap = [];
			foreach ($newParentTypeClassReflection->getActiveTemplateTypeMap()->getTypes() as $typeName => $type) {
				if (!array_key_exists($typeName, $ancestorMapping)) {
					continue;
				}

				$ancestorType = $ancestorMapping[$typeName];
				if (!$ancestorType->getBound()->isSuperTypeOf($type)->yes()) {
					continue;
				}

				if (!array_key_exists($ancestorType->getName(), $resolvedTypeMap)) {
					$resolvedTypeMap[$ancestorType->getName()] = $type;
					continue;
				}

				$resolvedTypeMap[$ancestorType->getName()] = TypeCombinator::union($resolvedTypeMap[$ancestorType->getName()], $type);
			}

			if ($isStatic) {
				return new GenericStaticType(
					$classReflection,
					$classReflection->typeMapToList(new TemplateTypeMap($resolvedTypeMap)),
					null,
					[],
				);
			}

			$types = $classReflection->typeMapToList(new TemplateTypeMap($resolvedTypeMap));
			return new GenericObjectType(
				$resolvedClassName,
				$types,
				classReflection: $classReflection->withTypes($types)->asFinal(),
			);
		}

		$resolvedTemplateTypeMap = $parametersAcceptor->getResolvedTemplateTypeMap();
		$types = $classReflection->typeMapToList($classReflection->getTemplateTypeMap());
		$newGenericType = new GenericObjectType(
			$resolvedClassName,
			$types,
			classReflection: $classReflection->withTypes($types)->asFinal(),
		);
		if ($isStatic) {
			$newGenericType = new GenericStaticType(
				$classReflection,
				$types,
				null,
				[],
			);
		}

		if (!$newGenericType->hasTemplateOrLateResolvableType()) {
			return $newGenericType;
		}

		return TypeTraverser::map($newGenericType, new GenericTypeTemplateTraverser($resolvedTemplateTypeMap));
	}

	/**
	 * Ported inside-out from the old TypeResolvingExprHandler::specifyTypes(): the
	 * constructor's @phpstan-assert narrowing is invoked on the already-processed
	 * argument results. The acceptor is $resolvedParametersAcceptor (type-driven,
	 * generics resolved by processArgs) rather than re-selected from the args on
	 * the asking scope. The subject's own default narrowing comes from
	 * DefaultNarrowingHelper instead of TypeSpecifier::specifyDefaultTypes(), which
	 * would re-enter this expression through TypeSpecifier::create().
	 *
	 * @param New_ $expr
	 */
	private function specifyTypes(MutatingScope $scope, Expr $expr, ?ParametersAcceptor $resolvedParametersAcceptor, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (
			!$expr->class instanceof Name
			|| !$this->reflectionProvider->hasClass($expr->class->toString())
		) {
			return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
		}

		$classReflection = $this->reflectionProvider->getClass($expr->class->toString());

		if ($classReflection->hasConstructor()) {
			$methodReflection = $classReflection->getConstructor();
			$asserts = $methodReflection->getAsserts();

			if ($asserts->getAll() !== [] && $resolvedParametersAcceptor !== null) {
				$asserts = $asserts->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
					$type,
					$resolvedParametersAcceptor->getResolvedTemplateTypeMap(),
					$resolvedParametersAcceptor instanceof ExtendedParametersAcceptor ? $resolvedParametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					TemplateTypeVariance::createInvariant(),
				));

				$specifiedTypes = $this->defaultNarrowingHelper->specifyTypesFromAsserts($context, $expr, $asserts, $resolvedParametersAcceptor, $scope);

				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}
			}
		}

		// A known class without (applicable) constructor asserts contributes no
		// narrowing entry, mirroring the old handler's empty return for this path
		// (a `new X()` is always a truthy object, so the default truthy/falsey
		// removal that path 1 emits would be a no-op here anyway).
		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

}
