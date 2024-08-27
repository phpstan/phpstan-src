<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;

/**
 * @api
 * @final
 */
class StatementExitPoint
{

	public function __construct(private Stmt $statement, private MutatingScope $scope)
	{
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
