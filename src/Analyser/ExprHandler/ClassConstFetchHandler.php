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
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<ClassConstFetch>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ClassConstFetchHandler.cpp')]
final class ClassConstFetchHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private ReflectionProvider $reflectionProvider,
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
		$nameResult = null;
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

		$result = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			variableFlow: VariableFlow::sequence($classResult !== null ? $classResult->getVariableFlow() : null, $nameResult !== null ? $nameResult->getVariableFlow() : null),
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

		return $result->withDependencies(Dependencies::merge(
			$classResult !== null ? $classResult->getDependencies() : null,
			$nameResult !== null ? $nameResult->getDependencies() : null,
			$this->getDependencies($beforeScope, $expr, $classResult, $result),
		));
	}

	/**
	 * The class the constant is fetched from, the class declaring it, and the classes in its type.
	 */
	private function getDependencies(MutatingScope $scope, ClassConstFetch $expr, ?ExpressionResult $classResult, ExpressionResult $result): ?Dependencies
	{
		$types = [$result->getType()];
		$classNames = [];
		if ($classResult !== null) {
			$types[] = $classResult->getType();
		} elseif ($expr->class instanceof Name) {
			$classNames[] = $scope->resolveName($expr->class);
		}

		if ($expr->name instanceof Identifier && $expr->name->toLowerString() !== 'class') {
			$constantName = $expr->name->toString();
			if ($classResult !== null) {
				$constantReflection = $scope->getConstantReflection($classResult->getType(), $constantName);
				if ($constantReflection !== null) {
					$classNames[] = $constantReflection->getDeclaringClass()->getName();
				}
			} elseif ($expr->class instanceof Name) {
				$className = $scope->resolveName($expr->class);
				if ($this->reflectionProvider->hasClass($className)) {
					$constantClassReflection = $this->reflectionProvider->getClass($className);
					if ($constantClassReflection->hasConstant($constantName)) {
						$classNames[] = $constantClassReflection->getConstant($constantName)->getDeclaringClass()->getName();
					}
				}
			}
		}

		return Dependencies::create($scope->getFile(), $types, $classNames);
	}

}
