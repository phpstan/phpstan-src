<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;

/**
 * @api
 * @final
 */
class MatchExpressionNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param MatchExpressionArm[] $arms
	 */
	public function __construct(
		private Expr $condition,
		private array $arms,
		Expr\Match_ $originalNode,
		private Scope $endScope,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getCondition(): Expr
	{
		return $this->condition;
	}

	/**
	 * @return MatchExpressionArm[]
	 */
	public function getArms(): array
	{
		return $this->arms;
	}

	public function getEndScope(): Scope
	{
		return $this->endScope;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_MatchExpression';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
