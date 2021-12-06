<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\NodeAbstract;

/** @api */
class InClosureNode extends NodeAbstract implements VirtualNode
{

	private Node\Expr\Closure $originalNode;

	public function __construct(Closure $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
	}

	public function getOriginalNode(): Closure
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_InClosureNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
