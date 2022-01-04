<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;

/** @api */
class InFunctionNode extends Node\Stmt implements VirtualNode
{

	public function __construct(private Node\Stmt\Function_ $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): Node\Stmt\Function_
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Stmt_InFunctionNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
