<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PostDec;
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
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<PostDec>
 */
#[AutowiredService]
final class PostDecHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private IncDecTypeHelper $incDecTypeHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PostDec;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$valueFlowWrite = VariableFlowBuilder::writeSite($expr->var, VariableWrite::KIND_POST_DEC, $scope, $storage);
		$valueContext = $valueFlowWrite !== null ? $context->enterDeep()->enterValueFlow($valueFlowWrite, false) : $context->enterDeep();
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $valueContext);

		// the virtual assign writes the decremented value - hand it the synthetic's
		// result so applyWrite composes off it instead of pricing the
		// unprocessed synthetic (and sentinel comparisons against it) on demand
		$virtualExpr = new PreDec($expr->var);
		$virtualExprResult = $this->expressionResultFactory->create(
			$varResult->getScope(),
			beforeScope: $scope,
			expr: $virtualExpr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: $this->incDecTypeHelper->getTypeCallback($expr->var, $varResult, false),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($virtualExpr, $context),
		);

		// processVirtualAssign() emits nodes (PropertyAssignNode) carrying the
		// synthetic pre-inc/dec as the assigned expression - store its result so
		// rule-side asks about it answer from the storage
		$nodeScopeResolver->storeExpressionResult($storage, $virtualExpr, $virtualExprResult);

		$assignedScope = $nodeScopeResolver->processVirtualAssign(
			$varResult->getScope(),
			$storage,
			$stmt,
			$expr->var,
			$virtualExpr,
			$nodeCallback,
			$virtualExprResult,
		)->getScope();

		return $this->expressionResultFactory->create(
			$assignedScope,
			beforeScope: $scope,
			expr: $expr,
			variableFlow: VariableFlow::sequence($varResult->getVariableFlow(), $valueFlowWrite !== null && $context->isValueConsumed() ? VariableFlow::inputs($valueFlowWrite->getId(), $context->getValueFlowTarget() !== null ? $context->getValueFlowTarget()->getId() : null) : null, VariableFlowBuilder::targetWrite($expr->var, VariableWrite::KIND_POST_DEC, $assignedScope, $storage)),
			hasYield: $varResult->hasYield(),
			isAlwaysTerminating: $varResult->isAlwaysTerminating(),
			throwPoints: $varResult->getThrowPoints(),
			impurePoints: $varResult->getImpurePoints(),
			// post-decrement evaluates to the variable's pre-mutation value
			typeCallback: static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType()),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
