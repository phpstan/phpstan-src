<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\IncDecTypeHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Variable\VariableWrite;
use function is_string;

/**
 * @implements ExprHandler<PreInc>
 */
#[AutowiredService]
final class PreIncHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private IncDecTypeHelper $incDecTypeHelper,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PreInc;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		// the write site is registered before the variable is walked: as a
		// statement or as the value of another write, the old value is only what
		// the new one is computed from; consumed by a sink, it is read
		$write = $expr->var instanceof Variable && is_string($expr->var->name)
			? $nodeScopeResolver->recordVariableWrite($expr->var, VariableWrite::KIND_INC_DEC)
			: null;
		$varContext = $context->enterDeep();
		$valueFlowTarget = $context->getValueFlowTarget();
		if ($write !== null && (!$context->isDeep() || $valueFlowTarget !== null)) {
			$varContext = $varContext->enterValueFlow($write, false);
		}
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $varContext);
		if ($write !== null && $valueFlowTarget !== null) {
			$nodeScopeResolver->copyVariableWriteDependencies($valueFlowTarget, $write);
		}

		$typeCallback = $this->incDecTypeHelper->getTypeCallback($expr->var, $varResult, true);
		$specifyTypesCallback = fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);

		// the result standing for the whole inc/dec expression - threaded into
		// processVirtualAssign() as the value to assign so applyWrite() reads it
		// directly instead of re-processing the node on demand (which would
		// recurse)
		$incDecValueResult = $this->expressionResultFactory->create(
			$varResult->getScope(),
			beforeScope: $scope,
			expr: $expr,
			hasYield: $varResult->hasYield(),
			isAlwaysTerminating: $varResult->isAlwaysTerminating(),
			throwPoints: $varResult->getThrowPoints(),
			impurePoints: $varResult->getImpurePoints(),
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
		);

		// processVirtualAssign() emits nodes (PropertyAssignNode) whose rules ask
		// about this whole expression - store its result first so those asks
		// answer from the storage; processExprNode() overwrites it with the
		// final result after this handler returns
		$nodeScopeResolver->storeExpressionResult($storage, $expr, $incDecValueResult);

		return $this->expressionResultFactory->create(
			$nodeScopeResolver->processVirtualAssign(
				$varResult->getScope(),
				$storage,
				$stmt,
				$expr->var,
				$expr,
				$nodeCallback,
				$incDecValueResult,
				VariableWrite::KIND_INC_DEC,
			)->getScope(),
			beforeScope: $scope,
			expr: $expr,
			hasYield: $varResult->hasYield(),
			isAlwaysTerminating: $varResult->isAlwaysTerminating(),
			throwPoints: $varResult->getThrowPoints(),
			impurePoints: $varResult->getImpurePoints(),
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
		);
	}

}
