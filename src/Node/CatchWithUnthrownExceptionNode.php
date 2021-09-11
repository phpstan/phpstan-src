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

	private Type $originalCaughtType;

	public function __construct(Catch_ $originalNode, Type $caughtType, Type $originalCaughtType)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
		$this->caughtType = $caughtType;
		$this->originalCaughtType = $originalCaughtType;
	}

	public function getOriginalNode(): Catch_
	{
		return $this->originalNode;
	}

	public function getCaughtType(): Type
	{
		return $this->caughtType;
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
