<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Type\Type;

class ArrayDimFetchAssignNode extends NodeAbstract implements VirtualNode
{

	private Expr $var;

	private Type $assignedType;

	public function __construct(Expr $var, Type $assignedType)
	{
		parent::__construct($var->getAttributes());
		$this->var = $var;
		$this->assignedType = $assignedType;
	}

	public function getVar(): Expr
	{
		return $this->var;
	}

	public function getAssignedType(): Type
	{
		return $this->assignedType;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_ArrayDimFetchAssignNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
