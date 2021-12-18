<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/** @api */
class InstantiationCallableNode extends Expr implements VirtualNode
{

	private Name|Expr $class;

	/**
	 * @param Expr|Name $class
	 * @param mixed[] $attributes
	 */
	public function __construct($class, array $attributes = [])
	{
		parent::__construct($attributes);
		$this->class = $class;
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
