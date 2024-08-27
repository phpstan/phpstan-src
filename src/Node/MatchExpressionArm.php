<?php declare(strict_types = 1);

namespace PHPStan\Node;

/**
 * @api
 * @final
 */
class MatchExpressionArm
{

	/**
	 * @param MatchExpressionArmCondition[] $conditions
	 */
	public function __construct(private MatchExpressionArmBody $body, private array $conditions, private int $line)
	{
	}

	public function getBody(): MatchExpressionArmBody
	{
		return $this->body;
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
