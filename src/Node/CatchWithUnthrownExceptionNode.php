<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeAbstract;
use PHPStan\Type\Type;

/** @api */
class CatchWithUnthrownExceptionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Catch_ $originalNode,
		private Type $caughtType,
		private Type $unthrownType,
		private Type $originalCaughtType,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): Catch_
	{
		return $this->originalNode;
	}

	public function getCaughtType(): Type
	{
		return $this->caughtType;
	}

	public function getUnthrownType(): Type
	{
		return $this->unthrownType;
	}

	public function getOriginalCaughtType(): Type
	{
		return $this->originalCaughtType;
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
