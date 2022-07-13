<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\LogicalXor;
use PHPStan\Analyser\Scope;

/** @api */
class BooleanXorNode extends Expr implements VirtualNode
{

	public function __construct(private LogicalXor $originalNode, private Scope $rightScope)
	{
		parent::__construct($originalNode->getAttributes());
	}

	/**
	 * @return LogicalXor
	 */
	public function getOriginalNode()
	{
		return $this->originalNode;
	}

	public function getRightScope(): Scope
	{
		return $this->rightScope;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_BooleanXorNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
