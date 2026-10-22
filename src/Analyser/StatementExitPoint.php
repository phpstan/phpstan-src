<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\CompiledIntoTurboExtension;

/**
 * @api
 */
#[CompiledIntoTurboExtension(new: ['pt_statement_exit_point_new', 'val'])]
final class StatementExitPoint
{

	public function __construct(private Stmt $statement, private Scope $scope)
	{
	}

	public function getStatement(): Stmt
	{
		return $this->statement;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
