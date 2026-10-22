<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\CompiledIntoTurboExtension;

/**
 * @api
 */
#[CompiledIntoTurboExtension(new: ['pt_end_statement_result_new', 'val'])]
final class EndStatementResult
{

	public function __construct(
		private Stmt $statement,
		private StatementResult $result,
	)
	{
	}

	public function getStatement(): Stmt
	{
		return $this->statement;
	}

	public function getResult(): StatementResult
	{
		return $this->result;
	}

}
