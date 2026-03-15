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
use PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use ReflectionProperty;
use Throwable;
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
		private ExpressionResultFactory $expressionResultFactory,
		private DynamicThrowTypeExtensionProvider $dynamicThrowTypeExtensionProvider,
		private MethodCallReturnTypeHelper $methodCallReturnTypeHelper,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
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
		$classResult = null;
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

					$methodThrowPoint = $this->getStaticMethodThrowPoint($methodReflection, $parametersAcceptor, $expr, $scope);
					if ($methodThrowPoint !== null) {
						$throwPoints[] = $methodThrowPoint;
					}

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
			$isAlwaysTerminating = $returnType instanceof NeverType && $returnType->isExplicit();
		}
		$argsResult = $nodeScopeResolver->processArgs($stmt, $methodReflection, null, $parametersAcceptor, $normalizedExpr, $scope, $storage, $nodeCallback, $context, $closureBindScope);
		$scope = $argsResult->getScope();
		$scopeFunction = $scope->getFunction();

		if (
			$methodReflection !== null
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
			$methodReflection !== null
			&& $this->rememberPossiblyImpureFunctionValues
			&& $parametersAcceptor !== null
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
			$methodReflection !== null
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

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: function (Expr $expr, MutatingScope $scope) use ($classResult): Type {
				if ($expr->name instanceof Identifier) {
					if ($expr->class instanceof Name) {
						$staticMethodCalledOnType = $this->resolveTypeByNameWithLateStaticBinding($scope, $expr->class, $expr->name);
					} elseif ($classResult !== null) {
						if ($scope->nativeTypesPromoted) {
							$staticMethodCalledOnType = $classResult->getTypeForScope($scope);
						} else {
							$staticMethodCalledOnType = TypeCombinator::removeNull($classResult->getTypeForScope($scope))->getObjectTypeOrClassStringObjectType();
						}
					} else {
						return new ErrorType();
					}

					if ($scope->nativeTypesPromoted) {
						$methodReflection = $scope->getMethodReflection(
							$staticMethodCalledOnType,
							$expr->name->name,
						);
						if ($methodReflection === null) {
							return new ErrorType();
						}

						return ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType();
					}

					$callType = $this->methodCallReturnTypeHelper->methodCallReturnType(
						$scope,
						$staticMethodCalledOnType,
						$expr->name->toString(),
						$expr,
					);

					return $callType ?? new ErrorType();
				}

				// TODO: handle dynamic method names
				return new MixedType();
			},
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	private function getStaticMethodThrowPoint(MethodReflection $methodReflection, ParametersAcceptor $parametersAcceptor, StaticCall $methodCall, MutatingScope $scope): ?InternalThrowPoint
	{
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicStaticMethodThrowTypeExtensions() as $extension) {
				if (!$extension->isStaticMethodSupported($methodReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromStaticMethodCall($methodReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $methodCall, false);
			}
		}

		if ($methodReflection->getThrowType() !== null) {
			$throwType = $methodReflection->getThrowType();
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $methodCall, true);
			}
		} elseif ($this->implicitThrows) {
			$methodReturnedType = $scope->getType($methodCall);
			if (!(new ObjectType(Throwable::class))->isSuperTypeOf($methodReturnedType)->yes()) {
				return InternalThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
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

}
