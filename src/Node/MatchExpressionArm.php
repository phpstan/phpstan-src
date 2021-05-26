<?php declare(strict_types = 1);

namespace PHPStan\Node;

/** @api */
class MatchExpressionArm
{

	/** @var MatchExpressionArmCondition[] */
	private array $conditions;

	private int $line;

	/**
	 * @param MatchExpressionArmCondition[] $conditions
	 * @param int $line
	 */
	public function __construct(array $conditions, int $line)
	{
		$this->conditions = $conditions;
		$this->line = $line;
	}

	/**
	 * @return MatchExpressionArmCondition[]
	 */
	public function getConditions(): array
	{
		return $this->conditions;
	}

	public function getLine(): int
	{
		return $this->line;
	}

}
