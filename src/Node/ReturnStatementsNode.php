<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PHPStan\Analyser\StatementResult;

/** @api */
interface ReturnStatementsNode extends VirtualNode
{

	/**
	 * @return ReturnStatement[]
	 */
	public function getReturnStatements(): array;

	public function getStatementResult(): StatementResult;

	public function returnsByRef(): bool;

	public function hasNativeReturnTypehint(): bool;

}
