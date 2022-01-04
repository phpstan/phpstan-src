<?php declare(strict_types = 1);

namespace PHPStan\Node;

/** @api */
class MatchExpressionArm
{

	/**
	 * @param MatchExpressionArmCondition[] $conditions
	 */
	public function __construct(private array $conditions, private int $line)
	{
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
