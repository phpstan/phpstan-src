<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/InternalEndStatementResult.cpp')]
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
