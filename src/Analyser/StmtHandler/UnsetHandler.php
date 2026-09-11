<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use ArrayAccess;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
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
use PHPStan\Analyser\VariableFlow;
use PHPStan\Analyser\VariableFlowBuilder;
use PHPStan\Analyser\VariableWriteOffset;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\Type;
use function array_merge;
use function is_string;
use function spl_object_id;

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
		$variableFlows = [];
		foreach ($stmt->vars as $var) {
			$scope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($scope, $var);
			$exprResult = $nodeScopeResolver->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments())->enterUnsetTarget());
			$variableFlows[] = VariableFlowBuilder::targetRead($var, $storage, $this->hasDestructionSideEffects($exprResult->getType()));
			$root = $var;
			while ($root instanceof ArrayDimFetch) {
				$root = $root->var;
			}
			if ($root instanceof Expr\Variable && is_string($root->name)) {
				$dimResult = $var instanceof ArrayDimFetch && $var->dim !== null ? $storage->findExpressionResult($var->dim) : null;
				$variableFlows[] = VariableFlow::discard(new VariableWrite($root->name, $var, spl_object_id($var), VariableWrite::KIND_ASSIGN, $var instanceof ArrayDimFetch, $dimResult !== null ? VariableWriteOffset::fromType($dimResult->getType()) : null, replacesOffset: !($var instanceof ArrayDimFetch) || $var->var === $root));
			}
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
						ExpressionContext::createDeep($context->shouldResolveTemplateArguments()),
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

			$scope = $scope->invalidateExpression(new ForeachValueByRefExpr($var));
		}

		// the Unset_ callback is deferred from processStmtNode(): it fires after
		// the unset targets were processed, with the entry scope, so rule-side
		// asks about them answer from the storage
		$nodeScopeResolver->callNodeCallback($nodeCallback, $stmt, $entryScope, $storage);

		return new InternalStatementResult($scope, hasYield: $hasYield, isAlwaysTerminating: false, exitPoints: [], throwPoints: $throwPoints, impurePoints: $impurePoints, variableFlow: VariableFlow::sequence(...$variableFlows));
	}

	private function hasDestructionSideEffects(Type $type): bool
	{
		if (!$type->isObject()->no() || !(new ResourceType())->isSuperTypeOf($type)->no()) {
			return true;
		}
		if ($type->isArray()->no()) {
			return false;
		}

		return $this->hasDestructionSideEffects($type->getIterableValueType());
	}

}
