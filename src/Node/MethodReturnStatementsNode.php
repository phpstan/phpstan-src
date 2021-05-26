<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;

/** @api */
class MethodReturnStatementsNode extends NodeAbstract implements ReturnStatementsNode
{

	private ClassMethod $classMethod;

	/** @var \PHPStan\Node\ReturnStatement[] */
	private array $returnStatements;

	private StatementResult $statementResult;

	/**
	 * @param \PhpParser\Node\Stmt\ClassMethod $method
	 * @param \PHPStan\Node\ReturnStatement[] $returnStatements
	 * @param \PHPStan\Analyser\StatementResult $statementResult
	 */
	public function __construct(
		ClassMethod $method,
		array $returnStatements,
		StatementResult $statementResult
	)
	{
		parent::__construct($method->getAttributes());
		$this->classMethod = $method;
		$this->returnStatements = $returnStatements;
		$this->statementResult = $statementResult;
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

	public function returnsByRef(): bool
	{
		return $this->classMethod->byRef;
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
