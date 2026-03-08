<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
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
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeUtils;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;
use function array_merge;
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
		private DynamicThrowTypeExtensionProvider $dynamicThrowTypeExtensionProvider,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
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

		$result = $nodeScopeResolver->processExprNode($stmt, $expr->var, $closureCallScope ?? $scope, $storage, $nodeCallback, $context->enterDeep());
		$hasYield = $result->hasYield();
		$throwPoints = $result->getThrowPoints();
		$impurePoints = $result->getImpurePoints();
		$isAlwaysTerminating = $result->isAlwaysTerminating();
		$scope = $result->getScope();
		if (isset($closureCallScope)) {
			$scope = $scope->restoreOriginalScopeAfterClosureBind($originalScope);
		}
		$parametersAcceptor = null;
		$methodReflection = null;
		$calledOnType = $scope->getType($expr->var);
		if ($expr->name instanceof Expr) {
			$methodNameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = array_merge($throwPoints, $methodNameResult->getThrowPoints());
			$scope = $methodNameResult->getScope();
		} else {
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

		$result = $nodeScopeResolver->processArgs(
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
		$scope = $result->getScope();

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
		$hasYield = $hasYield || $result->hasYield();
		$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();

		$result = new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);

		return $this->processInitializedProperties($nodeScopeResolver, $expr, $originalScope, $result);
	}

	private function processInitializedProperties(NodeScopeResolver $nodeScopeResolver, MethodCall $expr, MutatingScope $originalScope, ExpressionResult $handlerResult): ExpressionResult
	{
		$scope = $handlerResult->getScope();
		$calledOnType = $originalScope->getType($expr->var);
		if ($expr->name instanceof Expr) {
			return $handlerResult;
		}
		$methodName = $expr->name->name;
		$methodReflection = $originalScope->getMethodReflection($calledOnType, $methodName);
		if ($methodReflection === null) {
			return $handlerResult;
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
					hasYield: $handlerResult->hasYield(),
					isAlwaysTerminating: $handlerResult->isAlwaysTerminating(),
					throwPoints: $handlerResult->getThrowPoints(),
					impurePoints: $handlerResult->getImpurePoints(),
					truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
					falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
				);
			}
		}

		return $handlerResult;
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

}
