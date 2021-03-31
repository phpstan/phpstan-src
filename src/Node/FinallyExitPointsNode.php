<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementExitPoint;

class FinallyExitPointsNode extends NodeAbstract implements VirtualNode
{

	/** @var StatementExitPoint[] */
	private array $finallyExitPoints;

	/** @var StatementExitPoint[] */
	private array $tryCatchExitPoints;

	/**
	 * @param StatementExitPoint[] $finallyExitPoints
	 * @param StatementExitPoint[] $tryCatchExitPoints
	 */
	public function __construct(array $finallyExitPoints, array $tryCatchExitPoints)
	{
		parent::__construct([]);
		$this->finallyExitPoints = $finallyExitPoints;
		$this->tryCatchExitPoints = $tryCatchExitPoints;
	}

	/**
	 * @return StatementExitPoint[]
	 */
	public function getFinallyExitPoints(): array
	{
		return $this->finallyExitPoints;
	}

	/**
	 * @return StatementExitPoint[]
	 */
	public function getTryCatchExitPoints(): array
	{
		return $this->tryCatchExitPoints;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_FinallyExitPointsNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
