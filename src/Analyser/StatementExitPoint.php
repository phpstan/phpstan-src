<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;

class StatementExitPoint
{

	private Stmt $statement;

	private MutatingScope $scope;

	public function __construct(Stmt $statement, MutatingScope $scope)
	{
		$this->statement = $statement;
		$this->scope = $scope;
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
