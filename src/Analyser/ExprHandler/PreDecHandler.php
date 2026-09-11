<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PreDec;
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
use PHPStan\Analyser\VariableFlow;
use PHPStan\Analyser\VariableFlowBuilder;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Variable\VariableWrite;

/**
 * @implements ExprHandler<PreDec>
 */
#[AutowiredService]
final class PreDecHandler implements ExprHandler
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
		return $expr instanceof PreDec;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$valueFlowWrite = VariableFlowBuilder::writeSite($expr->var, VariableWrite::KIND_PRE_DEC, $scope, $storage);
		$valueContext = $valueFlowWrite !== null ? $context->enterDeep()->enterValueFlow($valueFlowWrite, false) : $context->enterDeep();
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $valueContext);

		$typeCallback = $this->incDecTypeHelper->getTypeCallback($expr->var, $varResult, false);
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

		$assignedScope = $nodeScopeResolver->processVirtualAssign(
			$varResult->getScope(),
			$storage,
			$stmt,
			$expr->var,
			$expr,
			$nodeCallback,
			$incDecValueResult,
		)->getScope();

		return $this->expressionResultFactory->create(
			$assignedScope,
			beforeScope: $scope,
			expr: $expr,
			variableFlow: VariableFlow::sequence($varResult->getVariableFlow(), $valueFlowWrite !== null && $context->isValueConsumed() ? VariableFlow::inputs($valueFlowWrite->getId(), $context->getValueFlowTarget() !== null ? $context->getValueFlowTarget()->getId() : null) : null, VariableFlowBuilder::targetWrite($expr->var, VariableWrite::KIND_PRE_DEC, $assignedScope, $storage)),
			hasYield: $varResult->hasYield(),
			isAlwaysTerminating: $varResult->isAlwaysTerminating(),
			throwPoints: $varResult->getThrowPoints(),
			impurePoints: $varResult->getImpurePoints(),
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
		);
	}

}
