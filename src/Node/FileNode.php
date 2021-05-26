<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\NodeAbstract;

/** @api */
class FileNode extends NodeAbstract implements VirtualNode
{

	/** @var \PhpParser\Node[] */
	private array $nodes;

	/**
	 * @param \PhpParser\Node[] $nodes
	 */
	public function __construct(array $nodes)
	{
		$firstNode = $nodes[0] ?? null;
		parent::__construct($firstNode !== null ? $firstNode->getAttributes() : []);
		$this->nodes = $nodes;
	}

	/**
	 * @return \PhpParser\Node[]
	 */
	public function getNodes(): array
	{
		return $this->nodes;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_FileNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
