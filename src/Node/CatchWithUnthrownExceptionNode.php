<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeAbstract;
use PHPStan\Type\Type;

/** @api */
class CatchWithUnthrownExceptionNode extends NodeAbstract implements VirtualNode
{

	private Catch_ $originalNode;

	private Type $caughtType;

	public function __construct(Catch_ $originalNode, Type $caughtType)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
		$this->caughtType = $caughtType;
	}

	public function getOriginalNode(): Catch_
	{
		return $this->originalNode;
	}

	public function getCaughtType(): Type
	{
		return $this->caughtType;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_CatchWithUnthrownExceptionNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
