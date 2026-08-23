<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\InlineHTML;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements StmtHandler<InlineHTML>
 */
#[AutowiredService]
final class InlineHtmlHandler implements StmtHandler
{

	public function supports(Stmt $stmt): bool
	{
		return $stmt instanceof InlineHTML;
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
		$impurePoints = [
			new ImpurePoint($scope, $stmt, 'betweenPhpTags', 'output between PHP opening and closing tags', true),
		];

		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: $impurePoints);
	}

}
