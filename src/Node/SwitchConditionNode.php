<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\NodeAbstract;

/**
 * Virtual node emitted once per `switch` statement. It pairs the switch subject
 * with each non-default `case` condition so rules can inspect the loose `==`
 * comparison the `switch` performs, using the scope captured at each case
 * (which already excludes the values matched by earlier cases).
 *
 * @api
 */
final class SwitchConditionNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param SwitchConditionArm[] $arms
	 */
	public function __construct(
		private Expr $subject,
		private array $arms,
		Switch_ $originalNode,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getSubject(): Expr
	{
		return $this->subject;
	}

	/**
	 * @return SwitchConditionArm[]
	 */
	public function getArms(): array
	{
		return $this->arms;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_SwitchCondition';
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
