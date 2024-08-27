<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementExitPoint;

final class DoWhileLoopConditionNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param StatementExitPoint[] $exitPoints
	 */
	public function __construct(private Expr $cond, private array $exitPoints)
	{
		parent::__construct($cond->getAttributes());
	}

	public function getCond(): Expr
	{
		return $this->cond;
	}

	/**
	 * @return StatementExitPoint[]
	 */
	public function getExitPoints(): array
	{
		return $this->exitPoints;
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
