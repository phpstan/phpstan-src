<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/** @api */
class FunctionCallableNode extends Expr implements VirtualNode
{

	private Name|Expr $name;

	/**
	 * @param mixed[] $attributes
	 */
	public function __construct(Expr|Name $name, array $attributes = [])
	{
		parent::__construct($attributes);
		$this->name = $name;
	}

	public function getName(): Expr|Name
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
