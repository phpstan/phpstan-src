<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
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
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<ClassConstFetch>
 */
#[AutowiredService]
final class ClassConstFetchHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ClassConstFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;

		$classResult = null;
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

		// the enclosing class is lexical - fixed at this node, identical on every
		// (possibly narrowed) scope the callback may later be invoked with - so
		// resolve it once here instead of reading it off the callback's scope.
		$classReflection = $beforeScope->isInClass() ? $beforeScope->getClassReflection() : null;

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: function (bool $nativeTypesPromoted) use ($expr, $classResult, $classReflection): Type {
				if (!$expr->name instanceof Identifier) {
					return new MixedType();
				}

				return $this->initializerExprTypeResolver->getClassConstFetchTypeByReflection(
					$expr->class,
					$expr->name->name,
					$classReflection,
					// getClassConstFetchTypeByReflection only invokes this for $expr->class
					// when it is an Expr, which is exactly when $classResult exists
					static function (Expr $e) use ($classResult, $nativeTypesPromoted): Type {
						if ($classResult === null) {
							throw new ShouldNotHappenException();
						}

						return $nativeTypesPromoted ? $classResult->getNativeType() : $classResult->getType();
					},
				);
			},
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
