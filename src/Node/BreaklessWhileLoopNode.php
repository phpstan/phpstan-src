<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\While_;
use PhpParser\NodeAbstract;

/** @api */
class BreaklessWhileLoopNode extends NodeAbstract implements VirtualNode
{

	private While_ $originalNode;

	public function __construct(While_ $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
	}

	public function getOriginalNode(): While_
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_BreaklessWhileLoop';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
