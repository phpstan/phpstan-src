<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/** @api */
class InstantiationCallableNode extends Expr implements VirtualNode
{

	/**
	 * @param mixed[] $attributes
	 */
	public function __construct(private Name|Expr $class, array $attributes = [])
	{
		parent::__construct($attributes);
	}

	/**
	 * @return Expr|Name
	 */
	public function getClass()
	{
		return $this->class;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_InstantiationCallableNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
