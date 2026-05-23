<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\Analyser\Traverser\ConstructorClassTemplateTraverser;
use PHPStan\Analyser\Traverser\GenericTypeTemplateTraverser;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Parser\NewAssignedToPropertyVisitor;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
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

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private DynamicThrowTypeExtensionProvider $dynamicThrowTypeExtensionProvider,
		private DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider,
		private PropertyReflectionFinder $propertyReflectionFinder,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof New_ && !$expr->isFirstClassCallable();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$parametersAcceptor = null;
		$constructorReflection = null;
		$classReflection = null;
		$isDynamic = false;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$normalizedExpr = $expr;
		if ($expr->class instanceof Name) {
			$className = $scope->resolveName($expr->class);

			[$constructorReflection, $classReflection, $parametersAcceptor, $constructorImpurePoints] = $this->processConstructorReflection($className, $expr, $scope, false);
			$impurePoints = array_merge($impurePoints, $constructorImpurePoints);

			if ($parametersAcceptor !== null) {
				$normalizedExpr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
			}

		} elseif ($expr->class instanceof Node\Stmt\Class_) {
			$classReflection = $this->reflectionProvider->getAnonymousClassReflection($expr->class, $scope); // populates $expr->class->name
			if ($classReflection->hasConstructor()) {
				$constructorReflection = $classReflection->getConstructor();
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
					$scope,
					$expr->getArgs(),
					$constructorReflection->getVariants(),
					$constructorReflection->getNamedArgumentsVariants(),
				);

				if ($constructorReflection->getDeclaringClass()->getName() === $classReflection->getName()) {
					$constructorResult = null;
					$nodeScopeResolver->processStmtNode($expr->class, $scope, $storage, static function (Node $node, Scope $scope) use ($nodeCallback, $classReflection, &$constructorResult): void {
						$nodeCallback($node, $scope);
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
					}, StatementContext::createTopLevel());
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
		} else {
			$isDynamic = true;
			$objectClasses = $scope->getType($expr)->getObjectClassNames();
			if (count($objectClasses) === 1) {
				$objectExprResult = $nodeScopeResolver->processExprNode($stmt, new New_(new Name($objectClasses[0])), $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
				$className = $objectClasses[0];
				$additionalThrowPoints = $objectExprResult->getThrowPoints();
			} else {
				$className = null;
				$additionalThrowPoints = [InternalThrowPoint::createImplicit($scope, $expr)];
			}

			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $classResult->getScope();
			$hasYield = $classResult->hasYield();
			$throwPoints = $classResult->getThrowPoints();
			$impurePoints = $classResult->getImpurePoints();
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();
			$throwPoints = array_merge($throwPoints, $additionalThrowPoints);

			if ($className !== null) {
				[$constructorReflection, $classReflection, $parametersAcceptor, $constructorImpurePoints] = $this->processConstructorReflection($className, $expr, $scope, true);
				$impurePoints = array_merge($impurePoints, $constructorImpurePoints);
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

		$argsResult = $nodeScopeResolver->processArgs($stmt, $constructorReflection, null, $parametersAcceptor, $normalizedExpr, $scope, $storage, $nodeCallback, $context);
		$scope = $argsResult->getScope();
		$hasYield = $hasYield || $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		if ($constructorReflection !== null && $parametersAcceptor !== null) {
			$className ??= $constructorReflection->getDeclaringClass()->getName();
			$constructorThrowPoint = $this->getConstructorThrowPoint($constructorReflection, $parametersAcceptor, $expr, new Name\FullyQualified($className), $expr->getArgs(), $scope, $context);
			if ($constructorThrowPoint !== null) {
				$throwPoints[] = $constructorThrowPoint;
			}
		} elseif ($classReflection === null || ($isDynamic && $constructorReflection === null && !$classReflection->isFinal())) {
			$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	/**
	 * @return array{?MethodReflection, ?ClassReflection, ?ParametersAcceptor, ImpurePoint[]}
	 */
	private function processConstructorReflection(string $className, New_ $expr, MutatingScope $scope, bool $isDynamic): array
	{
		$constructorReflection = null;
		$parametersAcceptor = null;
		$impurePoints = [];

		$classReflection = null;
		if ($this->reflectionProvider->hasClass($className)) {
			$classReflection = $this->reflectionProvider->getClass($className);
			if ($classReflection->hasConstructor()) {
				$constructorReflection = $classReflection->getConstructor();
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
					$scope,
					$expr->getArgs(),
					$constructorReflection->getVariants(),
					$constructorReflection->getNamedArgumentsVariants(),
				);
			}
		}

		if ($constructorReflection !== null) {
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
		} elseif ($classReflection === null) {
			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'new',
				'instantiation of unknown class',
				false,
			);
		} elseif ($isDynamic && !$classReflection->isFinal()) {
			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'new',
				sprintf('instantiation of class %s', $classReflection->getDisplayName()),
				false,
			);
		}

		return [$constructorReflection, $classReflection, $parametersAcceptor, $impurePoints];
	}

	/**
	 * @param list<Node\Arg> $args
	 */
	private function getConstructorThrowPoint(MethodReflection $constructorReflection, ParametersAcceptor $parametersAcceptor, New_ $new, Name $className, array $args, MutatingScope $scope, ExpressionContext $context): ?InternalThrowPoint
	{
		$methodCall = new StaticCall($className, $constructorReflection->getName(), $args);
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicStaticMethodThrowTypeExtensions() as $extension) {
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
			if (!$context->isInThrow()) {
				return InternalThrowPoint::createImplicit($scope, $methodCall);
			}

			if (!$constructorReflection->getDeclaringClass()->is(Throwable::class)) {
				return InternalThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->class instanceof Name) {
			return $this->exactInstantiation($scope, $expr, $expr->class);
		}
		if ($expr->class instanceof Node\Stmt\Class_) {
			$anonymousClassReflection = $this->reflectionProvider->getAnonymousClassReflection($expr->class, $scope);

			return new ObjectType($anonymousClassReflection->getName());
		}

		$exprType = $scope->getType($expr->class);
		return $exprType->getObjectTypeOrClassStringObjectType();
	}

	private function exactInstantiation(MutatingScope $scope, New_ $node, Name $className): Type
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

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$constructorMethod->getVariants(),
			$constructorMethod->getNamedArgumentsVariants(),
		);
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);

		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicReturnTypeExtensionRegistryProvider->getRegistry()->getDynamicStaticMethodReturnTypeExtensionsForClass($classReflection->getName()) as $dynamicStaticMethodReturnTypeExtension) {
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
		}

		if (count($resolvedTypes) > 0) {
			return TypeCombinator::union(...$resolvedTypes);
		}

		$methodResult = $scope->getType($methodCall);
		if ($methodResult instanceof NeverType && $methodResult->isExplicit()) {
			return $methodResult;
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
			$newParentType = $scope->getType($newParentNode);
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

}
