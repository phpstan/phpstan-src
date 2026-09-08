<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;

/**
 * Emitted for a return statement in a try or catch block once the following
 * finally block has been analysed. The scope it's emitted with is the scope
 * at the return statement with the changes made by the finally block applied.
 */
final class ReturnAfterFinallyNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Return_ $returnNode)
	{
		parent::__construct($returnNode->getAttributes());
	}

	public function getReturnNode(): Return_
	{
		return $this->returnNode;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ReturnAfterFinallyNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
