<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
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
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use Throwable;
use function array_map;
use function array_merge;
use function count;
use function sprintf;

/**
 * @implements ExprHandler<New_>
 */
#[AutowiredService]
final class NewHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private ReflectionProvider $reflectionProvider,
		private DynamicThrowTypeExtensionProvider $dynamicThrowTypeExtensionProvider,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof New_;
	}

	public function processExpr(Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$parametersAcceptor = null;
		$constructorReflection = null;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$className = null;
		$normalizedExpr = $expr;
		if ($expr->class instanceof Expr || $expr->class instanceof Name) {
			if ($expr->class instanceof Expr) {
				$objectClasses = $scope->getType($expr)->getObjectClassNames();
				if (count($objectClasses) === 1) {
					$objectExprResult = $this->nodeScopeResolver->processExprNode($stmt, new New_(new Name($objectClasses[0])), $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
					$className = $objectClasses[0];
					$additionalThrowPoints = $objectExprResult->getThrowPoints();
				} else {
					$additionalThrowPoints = [InternalThrowPoint::createImplicit($scope, $expr)];
				}

				$result = $this->nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
				$isAlwaysTerminating = $result->isAlwaysTerminating();
				foreach ($additionalThrowPoints as $throwPoint) {
					$throwPoints[] = $throwPoint;
				}
			} else {
				$className = $scope->resolveName($expr->class);
			}

			$classReflection = null;
			if ($className !== null && $this->reflectionProvider->hasClass($className)) {
				$classReflection = $this->reflectionProvider->getClass($className);
				if ($classReflection->hasConstructor()) {
					$constructorReflection = $classReflection->getConstructor();
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
						$scope,
						$expr->getArgs(),
						$constructorReflection->getVariants(),
						$constructorReflection->getNamedArgumentsVariants(),
					);
					$constructorThrowPoint = $this->getConstructorThrowPoint($constructorReflection, $parametersAcceptor, $classReflection, $expr, new Name\FullyQualified($className), $expr->getArgs(), $scope);
					if ($constructorThrowPoint !== null) {
						$throwPoints[] = $constructorThrowPoint;
					}
				}
			} else {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			}

			if ($constructorReflection !== null) {
				if (!$constructorReflection->hasSideEffects()->no()) {
					$certain = $constructorReflection->isPure()->no();
					$impurePoints[] = new ImpurePoint(
						$scope,
						$expr,
						'new',
						sprintf('instantiation of class %s', $constructorReflection->getDeclaringClass()->getDisplayName()),
						$certain,
					);
				}
			} elseif ($classReflection === null) {
				$impurePoints[] = new ImpurePoint(
					$scope,
					$expr,
					'new',
					'instantiation of unknown class',
					false,
				);
			}

			if ($parametersAcceptor !== null) {
				$normalizedExpr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
			}

		} else {
			$classReflection = $this->reflectionProvider->getAnonymousClassReflection($expr->class, $scope); // populates $expr->class->name
			if ($classReflection->hasConstructor()) {
				$constructorReflection = $classReflection->getConstructor();
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
					$scope,
					$expr->getArgs(),
					$constructorReflection->getVariants(),
					$constructorReflection->getNamedArgumentsVariants(),
				);

				if ($constructorReflection->getDeclaringClass()->getName() === $classReflection->getName()) {
					$constructorResult = null;
					$this->nodeScopeResolver->processStmtNode($expr->class, $scope, $storage, static function (Node $node, Scope $scope) use ($nodeCallback, $classReflection, &$constructorResult): void {
						$nodeCallback($node, $scope);
						if (!$node instanceof MethodReturnStatementsNode) {
							return;
						}
						if ($constructorResult !== null) {
							return;
						}
						$currentClassReflection = $node->getClassReflection();
						if ($currentClassReflection->getName() !== $classReflection->getName()) {
							return;
						}
						if (!$currentClassReflection->hasConstructor()) {
							return;
						}
						if ($currentClassReflection->getConstructor()->getName() !== $node->getMethodReflection()->getName()) {
							return;
						}
						$constructorResult = $node;
					}, StatementContext::createTopLevel());
					if ($constructorResult !== null) {
						$throwPoints = array_map(static fn (ThrowPoint $point) => InternalThrowPoint::createFromPublic($point), $constructorResult->getStatementResult()->getThrowPoints());
						$impurePoints = $constructorResult->getImpurePoints();
					}
				} else {
					$this->nodeScopeResolver->processStmtNode($expr->class, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
					$declaringClass = $constructorReflection->getDeclaringClass();
					$constructorThrowPoint = $this->getConstructorThrowPoint($constructorReflection, $parametersAcceptor, $classReflection, $expr, new Name\FullyQualified($declaringClass->getName()), $expr->getArgs(), $scope);
					if ($constructorThrowPoint !== null) {
						$throwPoints[] = $constructorThrowPoint;
					}

					if (!$constructorReflection->hasSideEffects()->no()) {
						$certain = $constructorReflection->isPure()->no();
						$impurePoints[] = new ImpurePoint(
							$scope,
							$expr,
							'new',
							sprintf('instantiation of class %s', $declaringClass->getDisplayName()),
							$certain,
						);
					}
				}
			} else {
				$this->nodeScopeResolver->processStmtNode($expr->class, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
			}
		}

		$result = $this->nodeScopeResolver->processArgs($stmt, $constructorReflection, null, $parametersAcceptor, $normalizedExpr, $scope, $storage, $nodeCallback, $context);
		$scope = $result->getScope();
		$hasYield = $hasYield || $result->hasYield();
		$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();

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

	/**
	 * @param list<Node\Arg> $args
	 */
	private function getConstructorThrowPoint(MethodReflection $constructorReflection, ParametersAcceptor $parametersAcceptor, ClassReflection $classReflection, New_ $new, Name $className, array $args, MutatingScope $scope): ?InternalThrowPoint
	{
		$methodCall = new StaticCall($className, $constructorReflection->getName(), $args);
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicStaticMethodThrowTypeExtensions() as $extension) {
				if (!$extension->isStaticMethodSupported($constructorReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromStaticMethodCall($constructorReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $new, false);
			}
		}

		if ($constructorReflection->getThrowType() !== null) {
			$throwType = $constructorReflection->getThrowType();
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $new, true);
			}
		} elseif ($this->implicitThrows) {
			if (!$classReflection->is(Throwable::class)) {
				return InternalThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
	}

}
