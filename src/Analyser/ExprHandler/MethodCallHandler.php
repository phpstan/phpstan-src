<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
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
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements ExprHandler<MethodCall>
 */
#[AutowiredService]
final class MethodCallHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DynamicThrowTypeExtensionProvider $dynamicThrowTypeExtensionProvider,
		private MethodCallReturnTypeHelper $methodCallReturnTypeHelper,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
		#[AutowiredParameter]
		private array $earlyTerminatingMethodCalls,
		private ReflectionProvider $reflectionProvider,
	)
	{
		$earlyTerminatingMethodNames = [];
		foreach ($this->earlyTerminatingMethodCalls as $methodNames) {
			foreach ($methodNames as $methodName) {
				$earlyTerminatingMethodNames[strtolower($methodName)] = true;
			}
		}
		$this->earlyTerminatingMethodNames = $earlyTerminatingMethodNames;
	}

	/** @var array<string, true> */
	private array $earlyTerminatingMethodNames;

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
		$hasYield = $varResult->hasYield();
		$throwPoints = $varResult->getThrowPoints();
		$impurePoints = $varResult->getImpurePoints();
		$isAlwaysTerminating = $varResult->isAlwaysTerminating();
		$scope = $varResult->getScope();
		if (isset($closureCallScope)) {
			$scope = $scope->restoreOriginalScopeAfterClosureBind($originalScope);
		}
		$parametersAcceptor = null;
		$methodReflection = null;
		$calledOnType = $varResult->getType();
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

				$methodThrowPoint = $this->getMethodThrowPoint($methodReflection, $parametersAcceptor, $expr, $scope);
				if ($methodThrowPoint !== null) {
					$throwPoints[] = $methodThrowPoint;
				}
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
			$isAlwaysTerminating = $returnType instanceof NeverType && $returnType->isExplicit();
		}
		if (!$isAlwaysTerminating && $expr->name instanceof Identifier && array_key_exists($expr->name->toLowerString(), $this->earlyTerminatingMethodNames)) {
			foreach ($calledOnType->getObjectClassNames() as $referencedClass) {
				if (!$this->reflectionProvider->hasClass($referencedClass)) {
					continue;
				}

				$classReflection = $this->reflectionProvider->getClass($referencedClass);
				foreach (array_merge([$referencedClass], $classReflection->getParentClassesNames(), $classReflection->getNativeReflection()->getInterfaceNames()) as $className) {
					if (!isset($this->earlyTerminatingMethodCalls[$className])) {
						continue;
					}

					if (in_array($expr->name->name, $this->earlyTerminatingMethodCalls[$className], true)) {
						$isAlwaysTerminating = true;
						break 2;
					}
				}
			}
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
			if ($methodReflection->getName() === '__construct' || $methodReflection->hasSideEffects()->yes()) {
				$nodeScopeResolver->callNodeCallback($nodeCallback, new InvalidateExprNode($normalizedExpr->var), $scope, $storage);
				$scope = $scope->invalidateExpression($normalizedExpr->var, true, $methodReflection->getDeclaringClass());
			} elseif ($this->rememberPossiblyImpureFunctionValues && $methodReflection->hasSideEffects()->maybe() && !$methodReflection->getDeclaringClass()->isBuiltin() && $parametersAcceptor !== null) {
				$scope = $scope->assignExpression(
					new PossiblyImpureCallExpr($normalizedExpr, $normalizedExpr->var, sprintf('%s::%s()', $methodReflection->getDeclaringClass()->getDisplayName(), $methodReflection->getName())),
					$parametersAcceptor->getReturnType(),
					new MixedType(),
				);
			}
			if ($parametersAcceptor !== null && !$methodReflection->isStatic()) {
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
			$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
		}
		$hasYield = $hasYield || $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		$result = $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: function (Expr $expr, MutatingScope $scope) use ($varResult): Type {
				if ($expr->name instanceof Identifier) {
					$varType = $varResult->getTypeForScope($scope);

					if ($scope->nativeTypesPromoted) {
						$methodReflection = $scope->getMethodReflection(
							$varType,
							$expr->name->name,
						);
						if ($methodReflection === null) {
							return new ErrorType();
						}

						return ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType();
					}

					$returnType = $this->methodCallReturnTypeHelper->methodCallReturnType(
						$scope,
						$varType,
						$expr->name->name,
						$expr,
					);

					return $returnType ?? new ErrorType();
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

		$calledOnType = $originalScope->getType($expr->var);
		if (!$expr->name instanceof Identifier) {
			return $result;
		}
		$methodName = $expr->name->name;
		$methodReflection = $originalScope->getMethodReflection($calledOnType, $methodName);
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
				return $this->expressionResultFactory->create(
					$expr,
					$scope,
					typeCallback: static fn (Expr $uninteresting, MutatingScope $scope) => $result->getTypeForScope($scope),
					hasYield: $result->hasYield(),
					isAlwaysTerminating: $result->isAlwaysTerminating(),
					throwPoints: $result->getThrowPoints(),
					impurePoints: $result->getImpurePoints(),
					truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
					falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
				);
			}
		}

		return $result;
	}

	private function getMethodThrowPoint(MethodReflection $methodReflection, ParametersAcceptor $parametersAcceptor, MethodCall $methodCall, MutatingScope $scope): ?InternalThrowPoint
	{
		$normalizedMethodCall = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $methodCall);
		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicMethodThrowTypeExtensions() as $extension) {
				if (!$extension->isMethodSupported($methodReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromMethodCall($methodReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $methodCall, false);
			}
		}

		if (
			in_array($methodReflection->getName(), ['invoke', 'invokeArgs'], true)
			&& in_array($methodReflection->getDeclaringClass()->getName(), [ReflectionMethod::class, ReflectionFunction::class], true)
		) {
			return InternalThrowPoint::createImplicit($scope, $methodCall);
		}

		$throwType = $methodReflection->getThrowType();
		if ($throwType === null) {
			$returnType = $parametersAcceptor->getReturnType();
			if ($returnType instanceof NeverType && $returnType->isExplicit()) {
				$throwType = new ObjectType(Throwable::class);
			}
		}

		if ($throwType !== null) {
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
		if ($expr->name instanceof Identifier && array_key_exists($expr->name->toLowerString(), $this->earlyTerminatingMethodNames)) {
			$calledOnType = $scope->getType($expr->var);
			foreach ($calledOnType->getObjectClassNames() as $referencedClass) {
				if (!$this->reflectionProvider->hasClass($referencedClass)) {
					continue;
				}

				$classReflection = $this->reflectionProvider->getClass($referencedClass);
				foreach (array_merge([$referencedClass], $classReflection->getParentClassesNames(), $classReflection->getNativeReflection()->getInterfaceNames()) as $className) {
					if (!isset($this->earlyTerminatingMethodCalls[$className])) {
						continue;
					}

					if (in_array($expr->name->name, $this->earlyTerminatingMethodCalls[$className], true)) {
						return new NeverType(true);
					}
				}
			}
		}

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

}
