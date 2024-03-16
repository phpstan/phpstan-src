<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;

/** @api */
interface ReturnStatementsNode extends VirtualNode
{

	/**
	 * @return list<ReturnStatement>
	 */
	public function getReturnStatements(): array;

	public function getStatementResult(): StatementResult;

	/**
	 * @return list<ExecutionEndNode>
	 */
	public function getExecutionEnds(): array;

	/**
	 * @return ImpurePoint[]
	 */
	public function getImpurePoints(): array;

	public function returnsByRef(): bool;

	public function hasNativeReturnTypehint(): bool;

	/**
	 * @return list<Yield_|YieldFrom>
	 */
	public function getYieldStatements(): array;

	public function isGenerator(): bool;

}
