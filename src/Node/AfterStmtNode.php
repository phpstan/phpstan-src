<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;

/** @api */
class AfterStmtNode extends Node\Stmt implements VirtualNode
{

	public function __construct(
		private Node\Stmt $originalNode,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): Node\Stmt
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Stmt_AfterStmtNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
