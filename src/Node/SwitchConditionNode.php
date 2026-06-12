<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;

/**
 * Virtual node emitted for every non-default `case` of a `switch`. It pairs the
 * switch subject with the case condition so rules can inspect the loose `==`
 * comparison the `switch` performs, using the scope captured at the case
 * condition (which already excludes the values matched by earlier cases).
 *
 * @api
 */
final class SwitchConditionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Expr $subject,
		private Expr $caseCondition,
		Node $originalNode,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getSubject(): Expr
	{
		return $this->subject;
	}

	public function getCaseCondition(): Expr
	{
		return $this->caseCondition;
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
