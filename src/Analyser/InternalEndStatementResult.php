<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\CompiledIntoTurboExtension;

#[CompiledIntoTurboExtension(new: ['pt_internal_end_statement_result_new', 'val'])]
final class InternalEndStatementResult
{

	public function __construct(
		private Stmt $statement,
		private InternalStatementResult $result,
	)
	{
	}

	public function toPublic(): EndStatementResult
	{
		return new EndStatementResult($this->statement, $this->result->toPublic());
	}

	public function getStatement(): Stmt
	{
		return $this->statement;
	}

	public function getResult(): InternalStatementResult
	{
		return $this->result;
	}

}
