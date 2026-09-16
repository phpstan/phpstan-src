<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @api
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/EndStatementResult.cpp')]
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
