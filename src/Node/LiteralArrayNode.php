<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr\Array_;
use PhpParser\NodeAbstract;

/**
 * @api
 * @final
 */
class LiteralArrayNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param LiteralArrayItem[] $itemNodes
	 */
	public function __construct(Array_ $originalNode, private array $itemNodes)
	{
		parent::__construct($originalNode->getAttributes());
	}

	/**
	 * @return LiteralArrayItem[]
	 */
	public function getItemNodes(): array
	{
		return $this->itemNodes;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_LiteralArray';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
