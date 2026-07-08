<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\ClosureBindArgVisitor;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;
use function count;

/**
 * @implements ExprHandler<ClassConstFetch>
 */
#[AutowiredService]
final class ClassConstFetchHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ClassConstFetch;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if (!$expr->name instanceof Identifier) {
			return new MixedType();
		}

		$classReflection = $scope->isInClass() ? $scope->getClassReflection() : null;
		$bindScopeReflection = $this->resolveClosureBindScopeReflection($scope, $expr->class);
		if ($bindScopeReflection !== null) {
			$classReflection = $bindScopeReflection;
		}

		return $this->initializerExprTypeResolver->getClassConstFetchTypeByReflection(
			$expr->class,
			$expr->name->name,
			$classReflection,
			static fn (Expr $e): Type => $scope->getType($e),
		);
	}

	/**
	 * Resolves the `Closure::bind()` scope class annotated on a `self`/`parent`/`static`
	 * class name node by {@see ClosureBindArgVisitor}. Returns null when the node is not
	 * inside a bound closure or the scope argument does not resolve to a single known class.
	 */
	private function resolveClosureBindScopeReflection(MutatingScope $scope, Expr|Name $class): ?ClassReflection
	{
		if (!$class instanceof Name || !$class->hasAttribute(ClosureBindArgVisitor::SCOPE_ATTRIBUTE_NAME)) {
			return null;
		}

		$scopeArg = $class->getAttribute(ClosureBindArgVisitor::SCOPE_ATTRIBUTE_NAME);
		if (!$scopeArg instanceof Expr) {
			// null attribute means the default "static" scope: keep the enclosing class.
			return null;
		}

		$scopeArgType = $scope->getType($scopeArg);
		$objectClassNames = $scopeArgType->getClassStringObjectType()->getObjectClassNames();
		if (count($objectClassNames) !== 1) {
			return null;
		}

		$className = $objectClassNames[0];
		if (!$this->reflectionProvider->hasClass($className)) {
			return null;
		}

		return $this->reflectionProvider->getClass($className);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;

		if ($expr->class instanceof Expr) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $classResult->getScope();
			$hasYield = $classResult->hasYield();
			$throwPoints = $classResult->getThrowPoints();
			$impurePoints = $classResult->getImpurePoints();
			$isAlwaysTerminating = $classResult->isAlwaysTerminating();
		} else {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->class, $scope, $storage);
		}

		if ($expr->name instanceof Identifier) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->name, $scope, $storage);
		} else {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $nameResult->getScope();
			$hasYield = $hasYield || $nameResult->hasYield();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
