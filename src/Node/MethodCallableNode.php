<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;

/** @api */
class MethodCallableNode extends Expr implements VirtualNode
{

	private Expr $var;

	private Identifier|Expr $name;

	private Expr\MethodCall $originalNode;

	public function __construct(Expr $var, Expr|Identifier $name, Expr\MethodCall $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
		$this->var = $var;
		$this->name = $name;
		$this->originalNode = $originalNode;
	}

	public function getVar(): Expr
	{
		return $this->var;
	}

	public function getName(): Expr|Identifier
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
