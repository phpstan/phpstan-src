<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;

/** @api */
class BooleanAndNode extends NodeAbstract implements VirtualNode
{

	private BooleanAnd|LogicalAnd $originalNode;

	private Scope $rightScope;

	/**
	 * @param BooleanAnd|LogicalAnd $originalNode
	 */
	public function __construct($originalNode, Scope $rightScope)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
		$this->rightScope = $rightScope;
	}

	/**
	 * @return BooleanAnd|LogicalAnd
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
		return 'PHPStan_Node_BooleanAndNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
