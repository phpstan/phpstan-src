<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;

/** @api */
class FunctionReturnStatementsNode extends NodeAbstract implements ReturnStatementsNode
{

	private Function_ $function;

	/** @var \PHPStan\Node\ReturnStatement[] */
	private array $returnStatements;

	private StatementResult $statementResult;

	/** @var ExecutionEndNode[] */
	private array $executionEnds;

	/**
	 * @param \PhpParser\Node\Stmt\Function_ $function
	 * @param \PHPStan\Node\ReturnStatement[] $returnStatements
	 * @param \PHPStan\Analyser\StatementResult $statementResult
	 * @param ExecutionEndNode[] $executionEnds
	 */
	public function __construct(
		Function_ $function,
		array $returnStatements,
		StatementResult $statementResult,
		array $executionEnds
	)
	{
		parent::__construct($function->getAttributes());
		$this->function = $function;
		$this->returnStatements = $returnStatements;
		$this->statementResult = $statementResult;
		$this->executionEnds = $executionEnds;
	}

	/**
	 * @return \PHPStan\Node\ReturnStatement[]
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
