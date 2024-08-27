<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeAbstract;

final class InForeachNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Foreach_ $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): Foreach_
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_InForeachNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
