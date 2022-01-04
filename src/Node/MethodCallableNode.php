<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;

/** @api */
class MethodCallableNode extends Expr implements VirtualNode
{

	private Identifier|Expr $name;

	/**
	 * @param Expr|Identifier $name
	 */
	public function __construct(private Expr $var, $name, private Expr\MethodCall $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
		$this->name = $name;
	}

	public function getVar(): Expr
	{
		return $this->var;
	}

	/**
	 * @return Expr|Identifier
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getOriginalNode(): Expr\MethodCall
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_MethodCallableNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
