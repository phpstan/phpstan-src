<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * @api
 * @final
 */
class FunctionCallableNode extends Expr implements VirtualNode
{

	public function __construct(private Name|Expr $name, private Expr\FuncCall $originalNode)
	{
		parent::__construct($this->originalNode->getAttributes());
	}

	/**
	 * @return Expr|Name
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getOriginalNode(): Expr\FuncCall
	{
		return $this->originalNode;
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
