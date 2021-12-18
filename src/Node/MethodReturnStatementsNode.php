<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;

/** @api */
class MethodReturnStatementsNode extends NodeAbstract implements ReturnStatementsNode
{

	private ClassMethod $classMethod;

	/** @var ReturnStatement[] */
	private array $returnStatements;

	private StatementResult $statementResult;

	/** @var ExecutionEndNode[] */
	private array $executionEnds;

	/**
	 * @param ReturnStatement[] $returnStatements
	 * @param ExecutionEndNode[] $executionEnds
	 */
	public function __construct(
		ClassMethod $method,
		array $returnStatements,
		StatementResult $statementResult,
		array $executionEnds,
	)
	{
		parent::__construct($method->getAttributes());
		$this->classMethod = $method;
		$this->returnStatements = $returnStatements;
		$this->statementResult = $statementResult;
		$this->executionEnds = $executionEnds;
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
		return $this->classMethod->byRef;
	}

	public function hasNativeReturnTypehint(): bool
	{
		return $this->classMethod->returnType !== null;
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
