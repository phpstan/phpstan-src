<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/** @api */
class StaticMethodCallableNode extends Expr implements VirtualNode
{

	private Name|Expr $class;

	private Identifier|Expr $name;

	private Expr\StaticCall $originalNode;

	public function __construct(Name|Expr $class, Identifier|Expr $name, Expr\StaticCall $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
		$this->class = $class;
		$this->name = $name;
		$this->originalNode = $originalNode;
	}

	public function getClass(): Expr|Name
	{
		return $this->class;
	}

	public function getName(): Identifier|Expr
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
