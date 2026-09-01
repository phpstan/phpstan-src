<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\TryCatch;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\CatchWithUnthrownExceptionNode;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\FinallyExitPointsNode;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Node\VariableAssignNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function array_fill_keys;
use function array_keys;
use function array_merge;
use function count;
use function is_string;

/**
 * @implements StmtHandler<TryCatch>
 */
#[AutowiredService]
final class TryCatchHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof TryCatch;
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
		$branchScopeResult = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context);
		$branchScope = $branchScopeResult->getScope();
		$finalScope = $branchScopeResult->isAlwaysTerminating() ? null : $branchScope;

		$exitPoints = [];
		$finallyExitPoints = [];
		$alwaysTerminating = $branchScopeResult->isAlwaysTerminating();
		$hasYield = $branchScopeResult->hasYield();

		if ($stmt->finally !== null) {
			$finallyScope = $branchScope;
		} else {
			$finallyScope = null;
		}
		foreach ($branchScopeResult->getExitPoints() as $exitPoint) {
			$finallyExitPoints[] = $exitPoint->toPublic();
			if ($exitPoint->getStatement() instanceof Node\Stmt\Expression && $exitPoint->getStatement()->expr instanceof Expr\Throw_) {
				continue;
			}
			if ($finallyScope !== null) {
				$finallyScope = $finallyScope->mergeWith($exitPoint->getScope());
			}
			$exitPoints[] = $exitPoint;
		}

		$throwPoints = $branchScopeResult->getThrowPoints();
		$impurePoints = $branchScopeResult->getImpurePoints();
		$throwPointsForLater = [];
		$pastCatchTypes = new NeverType();

		foreach ($stmt->catches as $catchNode) {
			$nodeScopeResolver->callNodeCallback($nodeCallback, $catchNode, $scope, $storage);

			$originalCatchTypes = [];
			$catchTypes = [];
			foreach ($catchNode->types as $catchNodeType) {
				$catchType = new ObjectType($catchNodeType->toString());
				$originalCatchTypes[] = $catchType;
				$catchTypes[] = TypeCombinator::remove($catchType, $pastCatchTypes);
			}

			$originalCatchType = TypeCombinator::union(...$originalCatchTypes);
			$catchType = TypeCombinator::union(...$catchTypes);
			$pastCatchTypes = TypeCombinator::union($pastCatchTypes, $originalCatchType);

			$matchingThrowPoints = [];
			$matchingCatchTypes = array_fill_keys(array_keys($originalCatchTypes), false);

			// throwable matches all
			foreach ($originalCatchTypes as $catchTypeIndex => $catchTypeItem) {
				if (!$catchTypeItem->isSuperTypeOf(new ObjectType(Throwable::class))->yes()) {
					continue;
				}

				foreach ($throwPoints as $throwPointIndex => $throwPoint) {
					$matchingThrowPoints[$throwPointIndex] = $throwPoint;
					$matchingCatchTypes[$catchTypeIndex] = true;
				}
			}

			// explicit only
			$onlyExplicitIsThrow = true;
			if (count($matchingThrowPoints) === 0) {
				foreach ($throwPoints as $throwPointIndex => $throwPoint) {
					foreach ($catchTypes as $catchTypeIndex => $catchTypeItem) {
						if ($catchTypeItem->isSuperTypeOf($throwPoint->getType())->no()) {
							continue;
						}

						$matchingCatchTypes[$catchTypeIndex] = true;
						if (!$throwPoint->isExplicit()) {
							continue;
						}
						$throwNode = $throwPoint->getNode();
						if (
							!$throwNode instanceof Expr\Throw_
							&& !($throwNode instanceof Node\Stmt\Expression && $throwNode->expr instanceof Expr\Throw_)
						) {
							$onlyExplicitIsThrow = false;
						}

						$matchingThrowPoints[$throwPointIndex] = $throwPoint;
					}
				}
			}

			// implicit only
			if (count($matchingThrowPoints) === 0 || $onlyExplicitIsThrow) {
				foreach ($throwPoints as $throwPointIndex => $throwPoint) {
					if ($throwPoint->isExplicit()) {
						continue;
					}

					foreach ($catchTypes as $catchTypeItem) {
						if ($catchTypeItem->isSuperTypeOf($throwPoint->getType())->no()) {
							continue;
						}

						$matchingThrowPoints[$throwPointIndex] = $throwPoint;
					}
				}
			}

			// include previously removed throw points
			if (count($matchingThrowPoints) === 0) {
				if ($originalCatchType->isSuperTypeOf(new ObjectType(Throwable::class))->yes()) {
					foreach ($branchScopeResult->getThrowPoints() as $originalThrowPoint) {
						if (!$originalThrowPoint->canContainAnyThrowable()) {
							continue;
						}

						$matchingThrowPoints[] = $originalThrowPoint;
						$matchingCatchTypes = array_fill_keys(array_keys($originalCatchTypes), true);
					}
				}
			}

			// emit error
			foreach ($matchingCatchTypes as $catchTypeIndex => $matched) {
				if ($matched) {
					continue;
				}
				$nodeScopeResolver->callNodeCallback($nodeCallback, new CatchWithUnthrownExceptionNode($catchNode, $catchTypes[$catchTypeIndex], $originalCatchTypes[$catchTypeIndex]), $scope, $storage);
			}

			if (count($matchingThrowPoints) === 0) {
				continue;
			}

			// recompute throw points
			$newThrowPoints = [];
			foreach ($throwPoints as $throwPoint) {
				$newThrowPoint = $throwPoint->subtractCatchType($originalCatchType);

				if ($newThrowPoint->getType() instanceof NeverType) {
					continue;
				}

				$newThrowPoints[] = $newThrowPoint;
			}
			$throwPoints = $newThrowPoints;

			$catchScope = null;
			foreach ($matchingThrowPoints as $matchingThrowPoint) {
				if ($catchScope === null) {
					$catchScope = $matchingThrowPoint->getScope();
				} else {
					$catchScope = $catchScope->mergeWith($matchingThrowPoint->getScope());
				}
			}

			$variableName = null;
			$catchWrite = null;
			if ($catchNode->var !== null) {
				if (!is_string($catchNode->var->name)) {
					throw new ShouldNotHappenException();
				}

				$variableName = $catchNode->var->name;
				$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($catchNode->var, new TypeExpr($catchType)), $scope, $storage);
				$catchWrite = $nodeScopeResolver->recordVariableWrite($catchNode->var, VariableWrite::KIND_CATCH);
			}

			$catchScopeResult = $nodeScopeResolver->processStmtNodesInternal($catchNode, $catchNode->stmts, $catchScope->enterCatchType($catchType, $variableName, $catchWrite, $catchWrite !== null && $variableName !== null ? $nodeScopeResolver->getVariableWriteMarkersToKill($variableName) : []), $storage, $nodeCallback, $context);
			$catchScopeForFinally = $catchScopeResult->getScope();

			$finalScope = $catchScopeResult->isAlwaysTerminating() ? $finalScope : $catchScopeResult->getScope()->mergeWith($finalScope);
			$alwaysTerminating = $alwaysTerminating && $catchScopeResult->isAlwaysTerminating();
			$hasYield = $hasYield || $catchScopeResult->hasYield();
			$catchThrowPoints = $catchScopeResult->getThrowPoints();
			$impurePoints = array_merge($impurePoints, $catchScopeResult->getImpurePoints());
			$throwPointsForLater = array_merge($throwPointsForLater, $catchThrowPoints);

			if ($finallyScope !== null) {
				$finallyScope = $finallyScope->mergeWith($catchScopeForFinally);
			}
			foreach ($catchScopeResult->getExitPoints() as $exitPoint) {
				$finallyExitPoints[] = $exitPoint->toPublic();
				if ($exitPoint->getStatement() instanceof Node\Stmt\Expression && $exitPoint->getStatement()->expr instanceof Expr\Throw_) {
					continue;
				}
				if ($finallyScope !== null) {
					$finallyScope = $finallyScope->mergeWith($exitPoint->getScope());
				}
				$exitPoints[] = $exitPoint;
			}

			foreach ($catchThrowPoints as $catchThrowPoint) {
				if ($finallyScope === null) {
					continue;
				}
				$finallyScope = $finallyScope->mergeWith($catchThrowPoint->getScope());
			}
		}

		if ($finalScope === null) {
			$finalScope = $scope;
		}

		foreach ($throwPoints as $throwPoint) {
			if ($finallyScope === null) {
				continue;
			}
			$finallyScope = $finallyScope->mergeWith($throwPoint->getScope());
		}

		if ($finallyScope !== null) {
			$originalFinallyScope = $finallyScope;
			$finallyResult = $nodeScopeResolver->processStmtNodesInternal($stmt->finally, $stmt->finally->stmts, $finallyScope, $storage, $nodeCallback, $context);
			$alwaysTerminating = $alwaysTerminating || $finallyResult->isAlwaysTerminating();
			$hasYield = $hasYield || $finallyResult->hasYield();
			$throwPointsForLater = array_merge($throwPointsForLater, $finallyResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $finallyResult->getImpurePoints());
			$finallyScope = $finallyResult->getScope();
			$finalScope = $finallyResult->isAlwaysTerminating() ? $finalScope : $finalScope->processFinallyScope($finallyScope, $originalFinallyScope);
			if (count($finallyResult->getExitPoints()) > 0 && $finallyResult->isAlwaysTerminating()) {
				$nodeScopeResolver->callNodeCallback($nodeCallback, new FinallyExitPointsNode(
					$finallyResult->toPublic()->getExitPoints(),
					$finallyExitPoints,
				), $scope, $storage);
			}
			$exitPoints = array_merge($exitPoints, $finallyResult->getExitPoints());
		}

		return new InternalStatementResult($finalScope, hasYield: $hasYield, isAlwaysTerminating: $alwaysTerminating, exitPoints: $exitPoints, throwPoints: array_merge($throwPoints, $throwPointsForLater), impurePoints: $impurePoints);
	}

}
