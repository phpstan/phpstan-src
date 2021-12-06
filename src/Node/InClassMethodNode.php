<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;

/** @api */
class InClassMethodNode extends Node\Stmt implements VirtualNode
{

	private Node\Stmt\ClassMethod $originalNode;

	public function __construct(Node\Stmt\ClassMethod $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
	}

	public function getOriginalNode(): Node\Stmt\ClassMethod
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Stmt_InClassMethodNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
