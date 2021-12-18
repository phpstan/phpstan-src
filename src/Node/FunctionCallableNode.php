<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/** @api */
class FunctionCallableNode extends Expr implements VirtualNode
{

	private Name|Expr $name;

	/**
	 * @param Expr|Name $name
	 * @param mixed[] $attributes
	 */
	public function __construct($name, array $attributes = [])
	{
		parent::__construct($attributes);
		$this->name = $name;
	}

	/**
	 * @return Expr|Name
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_FunctionCallableNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
