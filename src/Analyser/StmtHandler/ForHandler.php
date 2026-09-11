<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\For_;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Analyser\VariableFlowBuilder;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\TrinaryLogic;
use function array_last;
use function array_merge;
use function count;
use function in_array;
use function is_string;
use function spl_object_id;

/**
 * @implements StmtHandler<For_>
 */
#[AutowiredService]
final class ForHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof For_;
	}

	private function inferForLoopExpressions(NodeScopeResolver $nodeScopeResolver, For_ $stmt, Expr $lastCondExpr, MutatingScope $bodyScope, ExpressionResultStorage $storage): MutatingScope
	{
		// infer $items[$i] type from for ($i = 0; $i < count($items); $i++) {...}

		if (
			// $i = 0
			count($stmt->init) === 1
			&& $stmt->init[0] instanceof Assign
			&& $stmt->init[0]->var instanceof Variable
			&& $stmt->init[0]->expr instanceof Node\Scalar\Int_
			&& $stmt->init[0]->expr->value === 0
			// $i++ or ++$i
			&& count($stmt->loop) === 1
			&& ($stmt->loop[0] instanceof Expr\PreInc || $stmt->loop[0] instanceof Expr\PostInc)
			&& $stmt->loop[0]->var instanceof Variable
		) {
			// $i < count($items)
			if (
				$lastCondExpr instanceof BinaryOp\Smaller
				&& $lastCondExpr->left instanceof Variable
				&& $lastCondExpr->right instanceof FuncCall
				&& $lastCondExpr->right->name instanceof Name
				&& !$lastCondExpr->right->isFirstClassCallable()
				&& in_array($lastCondExpr->right->name->toLowerString(), ['count', 'sizeof'], true)
				&& count($lastCondExpr->right->getArgs()) > 0
				&& $lastCondExpr->right->getArgs()[0]->value instanceof Variable
				&& is_string($stmt->init[0]->var->name)
				&& $stmt->init[0]->var->name === $stmt->loop[0]->var->name
				&& $stmt->init[0]->var->name === $lastCondExpr->left->name
			) {
				$arrayArg = $lastCondExpr->right->getArgs()[0]->value;
				$arrayType = $nodeScopeResolver->readStoredResult($arrayArg, $storage)->getTypeOnScope($bodyScope, false);
				if ($arrayType->isList()->yes()) {
					$bodyScope = $bodyScope->assignExpression(
						new ArrayDimFetch($lastCondExpr->right->getArgs()[0]->value, $lastCondExpr->left),
						$arrayType->getIterableValueType(),
						$nodeScopeResolver->readStoredResult($arrayArg, $storage)->getTypeOnScope($bodyScope, true)->getIterableValueType(),
					);
				}
			}

			// count($items) > $i
			if (
				$lastCondExpr instanceof BinaryOp\Greater
				&& $lastCondExpr->right instanceof Variable
				&& $lastCondExpr->left instanceof FuncCall
				&& $lastCondExpr->left->name instanceof Name
				&& !$lastCondExpr->left->isFirstClassCallable()
				&& in_array($lastCondExpr->left->name->toLowerString(), ['count', 'sizeof'], true)
				&& count($lastCondExpr->left->getArgs()) > 0
				&& $lastCondExpr->left->getArgs()[0]->value instanceof Variable
				&& is_string($stmt->init[0]->var->name)
				&& $stmt->init[0]->var->name === $stmt->loop[0]->var->name
				&& $stmt->init[0]->var->name === $lastCondExpr->right->name
			) {
				$arrayArg = $lastCondExpr->left->getArgs()[0]->value;
				$arrayType = $nodeScopeResolver->readStoredResult($arrayArg, $storage)->getTypeOnScope($bodyScope, false);
				if ($arrayType->isList()->yes()) {
					$bodyScope = $bodyScope->assignExpression(
						new ArrayDimFetch($lastCondExpr->left->getArgs()[0]->value, $lastCondExpr->right),
						$arrayType->getIterableValueType(),
						$nodeScopeResolver->readStoredResult($arrayArg, $storage)->getTypeOnScope($bodyScope, true)->getIterableValueType(),
					);
				}
			}
		}

		return $bodyScope;
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
		$initScope = $scope;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$initFlow = [];
		$conditionFlow = [];
		foreach ($stmt->init as $initExpr) {
			$initResult = $nodeScopeResolver->processExprNode($stmt, $initExpr, $initScope, $storage, $nodeCallback, ExpressionContext::createTopLevel($context->shouldResolveTemplateArguments()));
			$initScope = $initResult->getScope();
			$initFlow[] = $initResult->getVariableFlow();
			$hasYield = $hasYield || $initResult->hasYield();
			$throwPoints = array_merge($throwPoints, $initResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $initResult->getImpurePoints());
		}
		$initTargets = [];
		foreach ($stmt->init as $initExpr) {
			if (!$initExpr instanceof Assign) {
				continue;
			}
			foreach (self::targetVariables($initExpr->var) as $variable) {
				$initTargets[spl_object_id($variable)] = true;
			}
		}

		$originalStorage = $storage;

		$bodyScope = $initScope;
		$isIterableAtLeastOnce = TrinaryLogic::createYes();
		$lastCondExpr = array_last($stmt->cond);
		if (count($stmt->cond) > 0) {
			$storage = $originalStorage->duplicate();
			$scope->pushExpressionResultStorage($storage);
			try {
				foreach ($stmt->cond as $condIndex => $condExpr) {
					$condResult = $nodeScopeResolver->processExprNode($stmt, $condExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep(resolveTemplateArguments: false));
					$initScope = $condResult->getScope();
					$conditionFlow[$condIndex] = $condResult->getVariableFlow();

					// only the last condition expression is relevant whether the loop continues
					// see https://www.php.net/manual/en/control-structures.for.php
					if ($condExpr === $lastCondExpr) {
						$condTruthiness = ($nodeScopeResolver->shouldTreatPhpDocTypesAsCertain() ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
						$isIterableAtLeastOnce = $isIterableAtLeastOnce->and($condTruthiness->isTrue());
					}

					$hasYield = $hasYield || $condResult->hasYield();
					$throwPoints = array_merge($throwPoints, $condResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $condResult->getImpurePoints());
					$bodyScope = $condResult->getTruthyScope();
				}
			} finally {
				$scope->popExpressionResultStorage();
			}
		}

		if ($context->isTopLevel()) {
			$count = 0;
			$prevEntryScope = null;
			do {
				$prevScope = $bodyScope;
				$storage = $originalStorage->duplicate();
				$bodyScope = $bodyScope->mergeWith($initScope);
				if ($prevEntryScope !== null && $bodyScope->equals($prevEntryScope)) {
					// walking is deterministic in the entry scope - an unchanged entry
					// reproduces the previous pass's exit, so the verification walk is skipped
					$bodyScope = $prevScope;
					break;
				}
				$prevEntryScope = $bodyScope;
				$scope->pushExpressionResultStorage($storage);
				try {
					if ($lastCondExpr !== null) {
						$bodyScope = $nodeScopeResolver->processExprNode($stmt, $lastCondExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep(resolveTemplateArguments: false))->getTruthyScope();
					}
					$bodyScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, new NoopNodeCallback(), $context->enterDeep()->withoutTemplateArgumentResolution())->filterOutLoopExitPoints();
					$bodyScope = $bodyScopeResult->getScope();
					foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
						$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
					}

					foreach ($stmt->loop as $loopExpr) {
						$exprResult = $nodeScopeResolver->processExprNode($stmt, $loopExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createTopLevel(resolveTemplateArguments: false));
						$bodyScope = $exprResult->getScope();
						$hasYield = $hasYield || $exprResult->hasYield();
						$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
						$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
					}
				} finally {
					$scope->popExpressionResultStorage();
				}

				if ($bodyScope->equals($prevScope)) {
					break;
				}

				if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
					$bodyScope = $prevScope->generalizeWith($bodyScope);
				}
				$count++;
			} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);
		}

		$storage = $originalStorage;
		$bodyScope = $bodyScope->mergeWith($initScope);

		$alwaysIterates = TrinaryLogic::createFromBoolean($context->isTopLevel());
		if ($lastCondExpr !== null) {
			// process the condition once and read the always-iterates check off
			// its result - the previous scope-based read was a guaranteed
			// storage miss (the condition was only stored into discarded
			// convergence duplicates) that re-priced it on demand
			$condResult = $nodeScopeResolver->processExprNode($stmt, $lastCondExpr, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
			$alwaysIterates = $alwaysIterates->and($condResult->getType()->toBoolean()->isTrue());
			$conditionFlow[count($stmt->cond) - 1] = $condResult->getVariableFlow();
			$bodyScope = $condResult->getTruthyScope();
			$bodyScope = $this->inferForLoopExpressions($nodeScopeResolver, $stmt, $lastCondExpr, $bodyScope, $storage);
		}

		$finalScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context)->filterOutLoopExitPoints();
		$finalScope = $finalScopeResult->getScope();
		foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
			$finalScope = $continueExitPoint->getScope()->mergeWith($finalScope);
		}

		$loopScope = $finalScope;
		foreach ($stmt->loop as $loopExpr) {
			$loopScope = $nodeScopeResolver->processExprNode($stmt, $loopExpr, $loopScope, $storage, $nodeCallback, ExpressionContext::createTopLevel($context->shouldResolveTemplateArguments()))->getScope();
		}
		$finalScope = $finalScope->generalizeWith($loopScope);

		if ($lastCondExpr !== null) {
			// the loop condition narrows the post-loop scope to its falsey branch,
			// priced on the GENERALIZED exit scope. The condition's stored result
			// was walked before generalizeWith() widened the counter, so its
			// verdict is stale here: `$k <= $d` with a literal `$k` reads as
			// always-true, whose falsey branch is unreachable - and that narrowed
			// every operand to never, killing the enclosing loop's counter
			// (a nested loop's counter never widened). Same shape as WhileHandler.
			$finalScope = $finalScope->applySpecifiedTypes(
				$nodeScopeResolver->processExprOnDemand($lastCondExpr, $finalScope, $storage->duplicate())
					->getSpecifiedTypesForScope($finalScope, TypeSpecifierContext::createFalsey()),
			);
		}

		$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
		if (count($breakExitPoints) > 0) {
			$breakScope = $alwaysIterates->yes() ? null : $finalScope;
			foreach ($breakExitPoints as $breakExitPoint) {
				$breakScope = $breakScope === null ? $breakExitPoint->getScope() : $breakScope->mergeWith($breakExitPoint->getScope());
			}
			$finalScope = $breakScope;
		}

		if ($isIterableAtLeastOnce->no() || $finalScopeResult->isAlwaysTerminating()) {
			if ($nodeScopeResolver->shouldPolluteScopeWithLoopInitialAssignments()) {
				$finalScope = $initScope;
			} else {
				$finalScope = $scope;
			}

		} elseif ($isIterableAtLeastOnce->maybe()) {
			if ($nodeScopeResolver->shouldPolluteScopeWithLoopInitialAssignments()) {
				$finalScope = $finalScope->mergeWith($initScope);
			} else {
				$finalScope = $finalScope->mergeWith($scope);
			}
		} else {
			if (!$nodeScopeResolver->shouldPolluteScopeWithLoopInitialAssignments()) {
				$finalScope = $finalScope->mergeWith($scope);
			}
		}

		if ($alwaysIterates->yes()) {
			$isAlwaysTerminating = count($finalScopeResult->getExitPointsByType(Break_::class)) === 0;
		} elseif ($isIterableAtLeastOnce->yes()) {
			$isAlwaysTerminating = $finalScopeResult->isAlwaysTerminating();
		} else {
			$isAlwaysTerminating = false;
		}

		$updateFlow = [];
		foreach ($stmt->loop as $loopExpr) {
			$updateFlow[] = VariableFlowBuilder::child($loopExpr, $storage);
		}
		$condition = VariableFlow::sequence(...$conditionFlow);
		$update = VariableFlow::sequence(...$updateFlow);
		$loop = $isIterableAtLeastOnce->no()
			? VariableFlow::sequence($condition, VariableFlow::dead(VariableFlow::sequence($finalScopeResult->getVariableFlow(), $update)))
			: VariableFlow::loop($condition, $finalScopeResult->getVariableFlow(), $update, $isIterableAtLeastOnce->yes(), !$alwaysIterates->yes());
		$initWrites = VariableFlowBuilder::writes(VariableFlow::sequence(...$initFlow));
		$bindings = [];
		foreach ($initWrites as $write) {
			if ($write->isOffsetWrite() || !isset($initTargets[$write->getId()])) {
				continue;
			}
			$bindings[] = $write;
		}
		$variableFlow = VariableFlow::loopStatement($stmt, VariableFlow::sequence(...[...$initFlow, $loop]), $bindings, [...$initWrites, ...VariableFlowBuilder::writes($update)]);
		return new InternalStatementResult(
			$finalScope->addTemplateArgumentConstraints($loopScope->getTemplateArgumentConstraints()),
			hasYield: $finalScopeResult->hasYield() || $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			exitPoints: $finalScopeResult->getExitPointsForOuterLoop(),
			throwPoints: array_merge($throwPoints, $finalScopeResult->getThrowPoints()),
			impurePoints: array_merge($impurePoints, $finalScopeResult->getImpurePoints()),
			variableFlow: $variableFlow,
		);
	}

	/**
	 * The variables an assignment target binds - the variable itself, or the
	 * variables of a destructuring list's items.
	 *
	 * @return list<Variable>
	 */
	private static function targetVariables(Expr $target): array
	{
		if ($target instanceof Variable) {
			return [$target];
		}
		if (!$target instanceof Expr\List_ && !$target instanceof Expr\Array_) {
			return [];
		}
		$variables = [];
		foreach ($target->items as $item) {
			if ($item === null) {
				continue;
			}
			foreach (self::targetVariables($item->value) as $variable) {
				$variables[] = $variable;
			}
		}

		return $variables;
	}

}
