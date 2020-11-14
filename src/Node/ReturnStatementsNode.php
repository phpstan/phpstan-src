<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PHPStan\Analyser\StatementResult;

interface ReturnStatementsNode extends VirtualNode
{

	/**
	 * @return \PHPStan\Node\ReturnStatement[]
	 */
	public function getReturnStatements(): array;

	public function getStatementResult(): StatementResult;

	public function returnsByRef(): bool;

}
