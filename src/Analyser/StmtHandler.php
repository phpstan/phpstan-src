<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * @template T of Stmt
 */
#[ExtensionInterface(tag: 'phpstan.stmtHandler')]
interface StmtHandler
{

	/** @phpstan-assert-if-true T $stmt */
	public function supports(Stmt $stmt): bool;

	/**
	 * @param T $stmt
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processStmt(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult;

}
