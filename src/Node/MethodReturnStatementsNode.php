<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use function count;

/**
 * @api
 * @final
 */
class MethodReturnStatementsNode extends NodeAbstract implements ReturnStatementsNode
{

	private ClassMethod $classMethod;

	/**
	 * @param list<ReturnStatement> $returnStatements
	 * @param list<Yield_|YieldFrom> $yieldStatements
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param ImpurePoint[] $impurePoints
	 */
	public function __construct(
		ClassMethod $method,
		private array $returnStatements,
		private array $yieldStatements,
		private StatementResult $statementResult,
		private array $executionEnds,
		private array $impurePoints,
		private ClassReflection $classReflection,
		private ExtendedMethodReflection $methodReflection,
	)
	{
		parent::__construct($method->getAttributes());
		$this->classMethod = $method;
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
		return $this->classMethod->byRef;
	}

	public function hasNativeReturnTypehint(): bool
	{
		return $this->classMethod->returnType !== null;
	}

	public function getMethodName(): string
	{
		return $this->classMethod->name->toString();
	}

	public function getYieldStatements(): array
	{
		return $this->yieldStatements;
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

	public function getMethodReflection(): ExtendedMethodReflection
	{
		return $this->methodReflection;
	}

	/**
	 * @return Stmt[]
	 */
	public function getStatements(): array
	{
		$stmts = $this->classMethod->getStmts();
		if ($stmts === null) {
			return [];
		}

		return $stmts;
	}

	public function isGenerator(): bool
	{
		return count($this->yieldStatements) > 0;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_MethodReturnStatementsNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
