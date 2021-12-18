<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/** @api */
class InstantiationCallableNode extends Expr implements VirtualNode
{

	private Name|Expr $class;

	/**
	 * @param mixed[] $attributes
	 */
	public function __construct(Expr|Name $class, array $attributes = [])
	{
		parent::__construct($attributes);
		$this->class = $class;
	}

	public function getClass(): Expr|Name
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
