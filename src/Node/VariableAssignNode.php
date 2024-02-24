<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;

class VariableAssignNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Expr\Variable $variable,
		private Expr $assignedExpr,
		private bool $assignOp,
	)
	{
		parent::__construct($variable->getAttributes());
	}

	public function getVariable(): Expr\Variable
	{
		return $this->variable;
	}

	public function getAssignedExpr(): Expr
	{
		return $this->assignedExpr;
	}

	public function isAssignOp(): bool
	{
		return $this->assignOp;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_VariableAssignNodeNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
