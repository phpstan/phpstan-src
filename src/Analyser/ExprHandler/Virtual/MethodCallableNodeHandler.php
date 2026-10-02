<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\Generics\ClosureSignatureInference;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\Dependency\DependencyTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<MethodCallableNode>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../../turbo-ext/src/MethodCallableNodeHandler.cpp')]
final class MethodCallableNodeHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof MethodCallableNode;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->getVar(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
		$scope = $varResult->getScope();
		if ($nodeScopeResolver->observingTemplateArgumentFrame($scope) !== null) {
			// the callable of a closure's method runs it where nothing follows
			// its signature
			$scope = $scope->addTemplateArgumentConstraints(ClosureSignatureInference::collectEscapes($varResult->getType()));
		}
		$hasYield = $varResult->hasYield();
		$throwPoints = $varResult->getThrowPoints();
		$impurePoints = $varResult->getImpurePoints();
		$isAlwaysTerminating = $varResult->isAlwaysTerminating();
		$nameResult = null;
		if ($expr->getName() instanceof Expr) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$scope = $nameResult->getScope();
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
		}

		$result = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			variableFlow: VariableFlow::sequence($varResult->getVariableFlow(), $nameResult !== null ? $nameResult->getVariableFlow() : null),
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: fn (bool $nativeTypesPromoted): Type => $this->resolveType($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope, $expr, $varResult),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);

		return $result->withDependencies(Dependencies::merge(
			$varResult->getDependencies(),
			$nameResult !== null ? $nameResult->getDependencies() : null,
			$this->getDependencies($beforeScope, $expr, $varResult, $result),
		));
	}

	/**
	 * The classes of the object, of the class declaring the method and of what calling it returns.
	 */
	private function getDependencies(MutatingScope $scope, MethodCallableNode $expr, ExpressionResult $varResult, ExpressionResult $result): ?Dependencies
	{
		$types = [];
		$callableType = $result->getType();
		if ($callableType->isCallable()->yes()) {
			foreach ($callableType->getCallableParametersAcceptors($scope) as $variant) {
				$types[] = $variant->getReturnType();
			}
		}
		$calledOnType = $varResult->getType();
		$types[] = $calledOnType;
		$classNames = [];
		$name = $expr->getName();
		if ($name instanceof Identifier) {
			$methodReflection = $scope->getMethodReflection($calledOnType, $name->toString());
			if ($methodReflection !== null) {
				$classNames[] = $methodReflection->getDeclaringClass()->getName();
				$types = [...$types, ...DependencyTypes::ofCalledMethod($methodReflection, true)];
			}
		}

		return Dependencies::create($scope->getFile(), $types, $classNames);
	}

	private function resolveType(MutatingScope $scope, MethodCallableNode $expr, ExpressionResult $varResult): Type
	{
		$originalNode = $expr->getOriginalNode();
		if (!$originalNode->name instanceof Identifier) {
			return new ObjectType(Closure::class);
		}

		// $originalNode->var is the same node as $expr->getVar(), processed in
		// processExpr - read its ExpressionResult instead of Scope::getType()
		$varType = $varResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		$method = $scope->getMethodReflection($varType, $originalNode->name->toString());
		if ($method === null) {
			return new ObjectType(Closure::class);
		}

		return $this->initializerExprTypeResolver->createFirstClassCallable(
			$method,
			$method->getVariants(),
			$scope->nativeTypesPromoted,
		);
	}

}
