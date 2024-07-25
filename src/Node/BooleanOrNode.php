<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PHPStan\Analyser\Scope;

/**
 * @api
 * @final
 */
class BooleanOrNode extends Expr implements VirtualNode
{

	public function __construct(private BooleanOr|LogicalOr $originalNode, private Scope $rightScope)
	{
		parent::__construct($originalNode->getAttributes());
	}

	/**
	 * @return BooleanOr|LogicalOr
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
		return 'PHPStan_Node_BooleanOrNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
