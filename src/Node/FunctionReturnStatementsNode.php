<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;
use function count;

/** @api */
class FunctionReturnStatementsNode extends NodeAbstract implements ReturnStatementsNode
{

	/**
	 * @param ReturnStatement[] $returnStatements
	 * @param list<Yield_|YieldFrom> $yieldStatements
	 * @param ExecutionEndNode[] $executionEnds
	 */
	public function __construct(
		private Function_ $function,
		private array $returnStatements,
		private array $yieldStatements,
		private StatementResult $statementResult,
		private array $executionEnds,
	)
	{
		parent::__construct($function->getAttributes());
	}

	/**
	 * @return ReturnStatement[]
	 */
	public function getReturnStatements(): array
	{
		return $this->returnStatements;
	}

	public function getStatementResult(): StatementResult
	{
		return $this->statementResult;
	}

	/**
	 * @return ExecutionEndNode[]
	 */
	public function getExecutionEnds(): array
	{
		return $this->executionEnds;
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

}
