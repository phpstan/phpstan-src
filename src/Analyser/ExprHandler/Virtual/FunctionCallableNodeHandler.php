<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
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
use PHPStan\Dependency\Dependencies;
use PHPStan\Dependency\DependencyTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<FunctionCallableNode>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../../turbo-ext/src/FunctionCallableNodeHandler.cpp')]
final class FunctionCallableNodeHandler implements ExprHandler
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
		return $expr instanceof FunctionCallableNode;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$throwPoints = [];
		$impurePoints = [];
		$hasYield = false;
		$isAlwaysTerminating = false;
		$nameResult = null;
		if ($expr->getName() instanceof Expr) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$scope = $nameResult->getScope();
			if ($nodeScopeResolver->observingTemplateArgumentFrame($scope) !== null && !self::isClosureObject($nameResult->getType())) {
				// the callable built from anything but a closure object runs the
				// closures it carries where nothing follows their signature
				$scope = $scope->addTemplateArgumentConstraints(ClosureSignatureInference::collectEscapes($nameResult->getType()));
			}
			$hasYield = $nameResult->hasYield();
			$throwPoints = $nameResult->getThrowPoints();
			$impurePoints = $nameResult->getImpurePoints();
			$isAlwaysTerminating = $nameResult->isAlwaysTerminating();
		}

		$result = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			variableFlow: $nameResult !== null ? $nameResult->getVariableFlow() : null,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: fn (bool $nativeTypesPromoted): Type => $this->resolveType($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope, $expr, $nameResult),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);

		return $result->withDependencies(Dependencies::merge(
			$nameResult !== null ? $nameResult->getDependencies() : null,
			$this->getDependencies($beforeScope, $expr, $nameResult, $result),
		));
	}

	/**
	 * The function the callable stands for, or the variants of the callable it is made of, and the
	 * classes in what calling it returns.
	 */
	private function getDependencies(MutatingScope $scope, FunctionCallableNode $expr, ?ExpressionResult $nameResult, ExpressionResult $result): ?Dependencies
	{
		$types = [];
		$callableType = $result->getType();
		if ($callableType->isCallable()->yes()) {
			foreach ($callableType->getCallableParametersAcceptors($scope) as $variant) {
				$types[] = $variant->getReturnType();
			}
		}
		$reflections = [];
		$name = $expr->getName();
		if ($name instanceof Name) {
			if ($this->reflectionProvider->hasFunction($name, $scope)) {
				$functionReflection = $this->reflectionProvider->getFunction($name, $scope);
				$reflections[] = $functionReflection;
				$types = [...$types, ...DependencyTypes::ofCalledVariants($functionReflection->getVariants()), ...DependencyTypes::ofAsserts($functionReflection->getAsserts())];
			}
		} elseif ($nameResult !== null) {
			$nameType = $nameResult->getType();
			if ($nameType->isCallable()->yes()) {
				foreach ($nameType->getCallableParametersAcceptors($scope) as $variant) {
					$types[] = $variant->getReturnType();
					$types = [...$types, ...DependencyTypes::ofCalledParameters($variant->getParameters())];
				}
			}
		}

		return Dependencies::create($scope->getFile(), $types, reflections: $reflections);
	}

	private function resolveType(MutatingScope $scope, FunctionCallableNode $expr, ?ExpressionResult $nameResult): Type
	{
		$originalNode = $expr->getOriginalNode();
		if ($originalNode->name instanceof Expr) {
			// $originalNode->name is the same node as $expr->getName(), processed
			// in processExpr exactly in this branch - read its ExpressionResult
			if ($nameResult === null) {
				throw new ShouldNotHappenException();
			}
			$callableType = $nameResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
			if (self::isClosureObject($callableType)) {
				// the first-class callable of a closure object is the object itself
				return $callableType;
			}
			if (!$callableType->isCallable()->yes()) {
				return new ObjectType(Closure::class);
			}

			return $this->initializerExprTypeResolver->createFirstClassCallable(
				null,
				$callableType->getCallableParametersAcceptors($scope),
				$scope->nativeTypesPromoted,
			);
		}

		return $this->initializerExprTypeResolver->getFirstClassCallableType($originalNode, InitializerExprContext::fromScope($scope), $scope->nativeTypesPromoted);
	}

	private static function isClosureObject(Type $type): bool
	{
		return (new ObjectType(Closure::class))->isSuperTypeOf($type)->yes();
	}

}
