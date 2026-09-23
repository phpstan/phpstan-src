<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Block;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\VariableFlow;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements StmtHandler<Block>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/BlockHandler.cpp')]
final class BlockHandler implements StmtHandler
{

	public function __construct(
		#[AutowiredParameter]
		private bool $polluteScopeWithBlock,
	)
	{
	}

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof Block;
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
		$result = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context);
		// like a loop body, the variable flow keeps the block optional whatever
		// polluteScopeWithBlock says: a write inside it does not make an
		// earlier write of the variable dead
		$variableFlow = VariableFlow::choice($result->getVariableFlow(), null);
		if ($this->polluteScopeWithBlock) {
			return $result->withVariableFlow($variableFlow);
		}

		return new InternalStatementResult(
			$scope->mergeWith($result->getScope()),
			hasYield: $result->hasYield(),
			isAlwaysTerminating: $result->isAlwaysTerminating(),
			exitPoints: $result->getExitPoints(),
			throwPoints: $result->getThrowPoints(),
			impurePoints: $result->getImpurePoints(),
			endStatements: $result->getEndStatements(),
			variableFlow: $variableFlow,
		);
	}

}
