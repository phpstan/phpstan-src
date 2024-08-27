<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * @api
 * @final
 */
class StaticMethodCallableNode extends Expr implements VirtualNode
{

	public function __construct(
		private Name|Expr $class,
		private Identifier|Expr $name,
		private Expr\StaticCall $originalNode,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	/**
	 * @return Expr|Name
	 */
	public function getClass()
	{
		return $this->class;
	}

	/**
	 * @return Identifier|Expr
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getOriginalNode(): Expr\StaticCall
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_StaticMethodCallableNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
