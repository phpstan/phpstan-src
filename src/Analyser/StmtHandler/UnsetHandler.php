<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use ArrayAccess;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Unset_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper;
use PHPStan\Analyser\ExprHandler\Helper\VirtualExprResultHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\VariableWriteOffset;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Type\ObjectType;
use function array_merge;
use function is_string;

/**
 * @implements StmtHandler<Unset_>
 */
#[AutowiredService]
final class UnsetHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Unset_;
	}

	public function __construct(private Container $container)
	{
	}

	public function processStmt(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$entryScope = $scope;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		foreach ($stmt->vars as $var) {
			$scope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($scope, $var);
			$exprResult = $nodeScopeResolver->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, ExpressionContext::createDeep()->enterUnsetTarget());
			$scope = $exprResult->getScope();
			$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $var);
			$hasYield = $hasYield || $exprResult->hasYield();
			$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
			if ($var instanceof ArrayDimFetch && $var->dim !== null) {
				$varType = $nodeScopeResolver->readStoredResult($var->var, $storage)->getTypeOnScope($scope, false);
				if (!$varType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
					$throwPoints = array_merge($throwPoints, $this->container->getByType(MethodThrowPointHelper::class)->getThrowPointsForCallOnType(
						$scope,
						ExpressionContext::createDeep(),
						$varType,
						new MethodCall(new TypeExpr($varType), 'offsetUnset'),
					));
				}

				// wrap the already-processed chain in ExistingArrayDimFetch nodes
				// referencing the original sub-expressions, so the virtual assign
				// reads their stored results instead of re-walking a clone
				$buildExistingChain = static function (Expr $node) use (&$buildExistingChain): Expr {
					if (!$node instanceof ArrayDimFetch || $node->dim === null) {
						return $node;
					}

					return new ExistingArrayDimFetch(
						$buildExistingChain($node->var),
						$node->dim,
					);
				};
				$clonedVar = $buildExistingChain($var->var);
				$unsetOffsetExpr = new UnsetOffsetExpr($var->var, $var->dim);
				$scope = $nodeScopeResolver->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$clonedVar,
					$unsetOffsetExpr,
					$nodeCallback,
					// composed from the chain results the unset target's walk just stored
					$this->container->getByType(VirtualExprResultHelper::class)->createUnsetOffsetExprResult(
						$scope,
						$unsetOffsetExpr,
						$nodeScopeResolver->readStoredResult($var->var, $storage),
						$nodeScopeResolver->readStoredResult($var->dim, $storage),
					),
				)->getScope();
			} elseif ($var instanceof PropertyFetch) {
				$scope = $scope->invalidateExpression($var);
				$impurePoints[] = new ImpurePoint(
					$scope,
					$var,
					'propertyUnset',
					'property unset',
					true,
				);
			} else {
				$scope = $scope->invalidateExpression($var);
			}

			$scope = $this->discardVariableWrites($nodeScopeResolver, $scope, $var, $storage);
			$scope = $scope->invalidateExpression(new ForeachValueByRefExpr($var));
		}

		// the Unset_ callback is deferred from processStmtNode(): it fires after
		// the unset targets were processed, with the entry scope, so rule-side
		// asks about them answer from the storage
		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $entryScope, $storage);

		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: false, exitPoints: [], throwPoints: $throwPoints, impurePoints: $impurePoints);
	}

	/**
	 * unset() discards the writes reaching the variable, or one constant
	 * offset of it, without reading them - they stay unused unless read before.
	 */
	private function discardVariableWrites(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, Expr $var, ExpressionResultStorage $storage): MutatingScope
	{
		if ($var instanceof Variable) {
			if (!is_string($var->name)) {
				return $scope;
			}

			return $scope->withoutVariableWriteMarkers($nodeScopeResolver->getVariableWriteMarkersToKill($var->name));
		}
		if (!$var instanceof ArrayDimFetch || $var->dim === null || !$var->var instanceof Variable || !is_string($var->var->name)) {
			return $scope;
		}
		$offset = VariableWriteOffset::fromType($nodeScopeResolver->readStoredResult($var->dim, $storage)->getType());
		if ($offset === null) {
			return $scope;
		}

		return $scope->withoutVariableWriteMarkers($nodeScopeResolver->getVariableOffsetWriteMarkersToKill($var->var->name, $offset));
	}

}
