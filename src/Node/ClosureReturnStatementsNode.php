<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;

/** @api */
class ClosureReturnStatementsNode extends NodeAbstract implements ReturnStatementsNode
{

	private Node\Expr\Closure $closureExpr;

	/**
	 * @param ReturnStatement[] $returnStatements
	 * @param array<int, Yield_|YieldFrom> $yieldStatements
	 */
	public function __construct(
		Closure $closureExpr,
		private array $returnStatements,
		private array $yieldStatements,
		private StatementResult $statementResult,
	)
	{
		parent::__construct($closureExpr->getAttributes());
		$this->closureExpr = $closureExpr;
	}

	public function getClosureExpr(): Closure
	{
		return $this->closureExpr;
	}

	public function hasNativeReturnTypehint(): bool
	{
		return $this->closureExpr->returnType !== null;
	}

	/**
	 * @return ReturnStatement[]
	 */
	public function getReturnStatements(): array
	{
		return $this->returnStatements;
	}

	/**
	 * @return array<int, Yield_|YieldFrom>
	 */
	public function getYieldStatements(): array
	{
		return $this->yieldStatements;
	}

	public function getStatementResult(): StatementResult
	{
		return $this->statementResult;
	}

	public function returnsByRef(): bool
	{
		return $this->closureExpr->byRef;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_ClosureReturnStatementsNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
