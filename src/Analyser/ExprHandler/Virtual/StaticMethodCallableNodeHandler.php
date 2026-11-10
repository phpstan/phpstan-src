<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\Dependency\DependencyTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\PartialFunctionApplicationParameters;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<StaticMethodCallableNode>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../../turbo-ext/src/StaticMethodCallableNodeHandler.cpp')]
final class StaticMethodCallableNodeHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof StaticMethodCallableNode;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$throwPoints = [];
		$impurePoints = [];
		$hasYield = false;
		$isAlwaysTerminating = false;
		$classResult = null;
		$nameResult = null;
		if ($expr->getClass() instanceof Expr) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->getClass(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$scope = $classResult->getScope();
			$hasYield = $classResult->hasYield();
			$throwPoints = $classResult->getThrowPoints();
			$impurePoints = $classResult->getImpurePoints();
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();
		}
		if ($expr->getName() instanceof Expr) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$scope = $nameResult->getScope();
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
		}

		// a partial function application evaluates its bound arguments when the
		// closure is created
		$argsVariableFlows = [];
		$argsDependencies = [];
		foreach ($expr->getOriginalNode()->getRawArgs() as $arg) {
			if (!$arg instanceof Arg) {
				continue;
			}
			$argResult = $nodeScopeResolver->processExprNode($stmt, $arg->value, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$scope = $argResult->getScope();
			$hasYield = $hasYield || $argResult->hasYield();
			$throwPoints = array_merge($throwPoints, $argResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $argResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $argResult->isAlwaysTerminating();
			$argsVariableFlows[] = $argResult->getVariableFlow();
			$argsDependencies[] = $argResult->getDependencies();
		}

		$result = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			variableFlow: VariableFlow::sequence($classResult?->getVariableFlow(), $nameResult?->getVariableFlow(), ...$argsVariableFlows),
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: fn (bool $nativeTypesPromoted): Type => $this->initializerExprTypeResolver->getFirstClassCallableType(
				$expr->getOriginalNode(),
				InitializerExprContext::fromScope($beforeScope),
				$nativeTypesPromoted,
				$expr->getOriginalNode()->isFirstClassCallable() ? null : PartialFunctionApplicationParameters::getBoundArgTypes($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope, $expr->getOriginalNode()),
			),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);

		return $result->withDependencies(Dependencies::merge(
			$classResult?->getDependencies(),
			$nameResult?->getDependencies(),
			$this->getDependencies($beforeScope, $expr, $classResult, $result),
			...$argsDependencies,
		));
	}

	/**
	 * The class, the class declaring the method and the classes in what calling it returns.
	 */
	private function getDependencies(MutatingScope $scope, StaticMethodCallableNode $expr, ?ExpressionResult $classResult, ExpressionResult $result): ?Dependencies
	{
		$types = [];
		$callableType = $result->getType();
		if ($callableType->isCallable()->yes()) {
			foreach ($callableType->getCallableParametersAcceptors($scope) as $variant) {
				$types[] = $variant->getReturnType();
			}
		}
		$classNames = [];
		$name = $expr->getName();
		$class = $expr->getClass();
		$methodReflection = null;
		if ($class instanceof Name) {
			$className = $scope->resolveName($class);
			$classNames[] = $className;
			if ($name instanceof Identifier && $this->reflectionProvider->hasClass($className)) {
				$methodClassReflection = $this->reflectionProvider->getClass($className);
				if ($methodClassReflection->hasMethod($name->toString())) {
					$methodReflection = $methodClassReflection->getMethod($name->toString(), $scope);
				}
			}
		} elseif ($classResult !== null) {
			$classType = $classResult->getType();
			$types[] = $classType;
			if ($name instanceof Identifier) {
				$methodReflection = $scope->getMethodReflection($classType, $name->toString());
			}
		}

		if ($methodReflection !== null) {
			$classNames[] = $methodReflection->getDeclaringClass()->getName();
			$types = [...$types, ...DependencyTypes::ofCalledMethod($methodReflection, false)];
		}

		return Dependencies::create($scope->getFile(), $types, $classNames);
	}

}
