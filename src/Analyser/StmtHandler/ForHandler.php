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
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\TrinaryLogic;
use function array_last;
use function array_merge;
use function count;
use function in_array;
use function is_string;

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

	private function inferForLoopExpressions(For_ $stmt, Expr $lastCondExpr, MutatingScope $bodyScope): MutatingScope
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
				$arrayType = $bodyScope->getType($arrayArg);
				if ($arrayType->isList()->yes()) {
					$bodyScope = $bodyScope->assignExpression(
						new ArrayDimFetch($lastCondExpr->right->getArgs()[0]->value, $lastCondExpr->left),
						$arrayType->getIterableValueType(),
						$bodyScope->getNativeType($arrayArg)->getIterableValueType(),
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
				$arrayType = $bodyScope->getType($arrayArg);
				if ($arrayType->isList()->yes()) {
					$bodyScope = $bodyScope->assignExpression(
						new ArrayDimFetch($lastCondExpr->left->getArgs()[0]->value, $lastCondExpr->right),
						$arrayType->getIterableValueType(),
						$bodyScope->getNativeType($arrayArg)->getIterableValueType(),
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
		foreach ($stmt->init as $initExpr) {
			$initResult = $nodeScopeResolver->processExprNode($stmt, $initExpr, $initScope, $storage, $nodeCallback, ExpressionContext::createTopLevel());
			$initScope = $initResult->getScope();
			$hasYield = $hasYield || $initResult->hasYield();
			$throwPoints = array_merge($throwPoints, $initResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $initResult->getImpurePoints());
		}

		$originalStorage = $storage;

		$bodyScope = $initScope;
		$isIterableAtLeastOnce = TrinaryLogic::createYes();
		$lastCondExpr = array_last($stmt->cond);
		if (count($stmt->cond) > 0) {
			$storage = $originalStorage->duplicate();
			foreach ($stmt->cond as $condExpr) {
				$condResult = $nodeScopeResolver->processExprNode($stmt, $condExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep());
				$initScope = $condResult->getScope();

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
				if ($lastCondExpr !== null) {
					$bodyScope = $nodeScopeResolver->processExprNode($stmt, $lastCondExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep())->getTruthyScope();
				}
				$bodyScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, new NoopNodeCallback(), $context->enterDeep())->filterOutLoopExitPoints();
				$bodyScope = $bodyScopeResult->getScope();
				foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
				}

				foreach ($stmt->loop as $loopExpr) {
					$exprResult = $nodeScopeResolver->processExprNode($stmt, $loopExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createTopLevel());
					$bodyScope = $exprResult->getScope();
					$hasYield = $hasYield || $exprResult->hasYield();
					$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
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
			$alwaysIterates = $alwaysIterates->and($bodyScope->getType($lastCondExpr)->toBoolean()->isTrue());
			$bodyScope = $nodeScopeResolver->processExprNode($stmt, $lastCondExpr, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep())->getTruthyScope();
			$bodyScope = $this->inferForLoopExpressions($stmt, $lastCondExpr, $bodyScope);
		}

		$finalScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context)->filterOutLoopExitPoints();
		$finalScope = $finalScopeResult->getScope();
		foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
			$finalScope = $continueExitPoint->getScope()->mergeWith($finalScope);
		}

		$loopScope = $finalScope;
		foreach ($stmt->loop as $loopExpr) {
			$loopScope = $nodeScopeResolver->processExprNode($stmt, $loopExpr, $loopScope, $storage, $nodeCallback, ExpressionContext::createTopLevel())->getScope();
		}
		$finalScope = $finalScope->generalizeWith($loopScope);

		if ($lastCondExpr !== null) {
			$finalScope = $finalScope->filterByFalseyValue($lastCondExpr);
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

		return new InternalStatementResult(
			$finalScope,
			hasYield: $finalScopeResult->hasYield() || $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			exitPoints: $finalScopeResult->getExitPointsForOuterLoop(),
			throwPoints: array_merge($throwPoints, $finalScopeResult->getThrowPoints()),
			impurePoints: array_merge($impurePoints, $finalScopeResult->getImpurePoints()),
		);
	}

}
