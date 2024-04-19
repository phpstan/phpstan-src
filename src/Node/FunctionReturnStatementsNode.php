<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\FunctionReflection;
use function count;

/** @api */
class FunctionReturnStatementsNode extends NodeAbstract implements ReturnStatementsNode
{

	/**
	 * @param list<ReturnStatement> $returnStatements
	 * @param list<Yield_|YieldFrom> $yieldStatements
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param ImpurePoint[] $impurePoints
	 */
	public function __construct(
		private Function_ $function,
		private array $returnStatements,
		private array $yieldStatements,
		private StatementResult $statementResult,
		private array $executionEnds,
		private array $impurePoints,
		private FunctionReflection $functionReflection,
	)
	{
		parent::__construct($function->getAttributes());
	}

	public function getReturnStatements(): array
	{
		return $this->returnStatements;
	}

	public function getStatementResult(): StatementResult
	{
		return $this->statementResult;
	}

	public function getExecutionEnds(): array
	{
		return $this->executionEnds;
	}

	public function getImpurePoints(): array
	{
		return $this->impurePoints;
	}

	public function returnsByRef(): bool
	{
		return $this->function->byRef;
	}

	public function hasNativeReturnTypehint(): bool
	{
		return $this->function->returnType !== null;
	}

	public function getYieldStatements(): array
	{
		return $this->yieldStatements;
	}

	public function isGenerator(): bool
	{
		return count($this->yieldStatements) > 0;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_FunctionReturnStatementsNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

	public function getFunctionReflection(): FunctionReflection
	{
		return $this->functionReflection;
	}

	/**
	 * @return Stmt[]
	 */
	public function getStatements(): array
	{
		return $this->function->getStmts();
	}

}
