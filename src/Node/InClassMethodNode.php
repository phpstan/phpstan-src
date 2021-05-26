<?php declare(strict_types = 1);

namespace PHPStan\Node;

/** @api */
class InClassMethodNode extends \PhpParser\Node\Stmt implements VirtualNode
{

	private \PhpParser\Node\Stmt\ClassMethod $originalNode;

	public function __construct(\PhpParser\Node\Stmt\ClassMethod $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
	}

	public function getOriginalNode(): \PhpParser\Node\Stmt\ClassMethod
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
