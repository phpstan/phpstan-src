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
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
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
		private MethodCallReturnTypeHelper $methodCallReturnTypeHelper,
		private MethodThrowPointHelper $methodThrowPointHelper,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof StaticCall && !$expr->isFirstClassCallable();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		if ($expr->class instanceof Expr) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $classResult->hasYield();
			$throwPoints = array_merge($throwPoints, $classResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $classResult->getImpurePoints());
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();

			$scope = $classResult->getScope();
		}

		$parametersAcceptor = null;
		$methodReflection = null;
		$closureBindScope = null;
		if ($expr->name instanceof Identifier) {
			if ($expr->class instanceof Name) {
				$classType = $scope->resolveTypeByName($expr->class);
				$methodName = $expr->name->name;
				if ($classType->hasMethod($methodName)->yes()) {
					$methodReflection = $classType->getMethod($methodName, $scope);
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
						$scope,
						$expr->getArgs(),
						$methodReflection->getVariants(),
						$methodReflection->getNamedArgumentsVariants(),
					);

					$declaringClass = $methodReflection->getDeclaringClass();
					if (
						$declaringClass->getName() === 'Closure'
						&& strtolower($methodName) === 'bind'
					) {
						$thisType = null;
						$nativeThisType = null;
						if (isset($expr->getArgs()[1])) {
							$argType = $scope->getType($expr->getArgs()[1]->value);
							if ($argType->isNull()->yes()) {
								$thisType = null;
							} else {
								$thisType = $argType;
							}

							$nativeArgType = $scope->getNativeType($expr->getArgs()[1]->value);
							if ($nativeArgType->isNull()->yes()) {
								$nativeThisType = null;
							} else {
								$nativeThisType = $nativeArgType;
							}
						}
						$scopeClasses = ['static'];
						if (isset($expr->getArgs()[2])) {
							$argValue = $expr->getArgs()[2]->value;
							$argValueType = $scope->getType($argValue);

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
						$closureBindScope = $scope->enterClosureBind($thisType, $nativeThisType, $scopeClasses);
					}
				} else {
					$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
				}
			} elseif ($expr->class instanceof Expr) {
				$classType = $scope->getType($expr->class)->getObjectTypeOrClassStringObjectType();
				$methodName = $expr->name->name;
				$methodReflection = $scope->getMethodReflection($classType, $methodName);
				if ($methodReflection !== null) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
						$scope,
						$expr->getArgs(),
						$methodReflection->getVariants(),
						$methodReflection->getNamedArgumentsVariants(),
					);
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
		$argsResult = $nodeScopeResolver->processArgs($stmt, $methodReflection, null, $parametersAcceptor, $normalizedExpr, $scope, $storage, $nodeCallback, $context, $closureBindScope);
		$scope = $argsResult->getScope();
		$scopeFunction = $scope->getFunction();

		if ($methodReflection !== null) {
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
			$scope = $scope->assignExpression(
				new PossiblyImpureCallExpr($normalizedExpr, new Variable('this'), sprintf('%s::%s()', $methodReflection->getDeclaringClass()->getDisplayName(), $methodReflection->getName())),
				$parametersAcceptor->getReturnType(),
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

		$hasYield = $hasYield || $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		return new ExpressionResult(
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
					// Asserts narrow the arguments, but the call expression itself
					// must still be remembered as truthy/falsey so that re-evaluating
					// it in the same branch keeps the narrowed result. Keep the
					// asserts' root expression.
					return $specifiedTypes
						->unionWith($typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope))
						->setRootExpr($specifiedTypes->getRootExpr());
				}
			}
		}

		return $typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
	}

}
