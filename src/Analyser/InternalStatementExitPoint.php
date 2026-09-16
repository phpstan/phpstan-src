<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/InternalStatementExitPoint.cpp')]
final class InternalStatementExitPoint
{

	public function __construct(private Stmt $statement, private MutatingScope $scope)
	{
	}

	public function toPublic(): StatementExitPoint
	{
		return new StatementExitPoint($this->statement, $this->scope);
	}

	public function getStatement(): Stmt
	{
		return $this->statement;
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

}
