<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementExitPoint;
use PHPStan\Analyser\ThrowPoint;

final class DoWhileLoopConditionNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param StatementExitPoint[] $exitPoints
	 * @param ThrowPoint[] $throwPoints
	 */
	public function __construct(private Expr $cond, private array $exitPoints, private array $throwPoints)
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

	/**
	 * @return ThrowPoint[]
	 */
	public function getThrowPoints(): array
	{
		return $this->throwPoints;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ClosureReturnStatementsNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
