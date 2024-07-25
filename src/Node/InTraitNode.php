<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;

/**
 * @api
 * @final
 */
class InTraitNode extends Node\Stmt implements VirtualNode
{

	public function __construct(private Node\Stmt\Trait_ $originalNode, private ClassReflection $traitReflection)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): Node\Stmt\Trait_
	{
		return $this->originalNode;
	}

	public function getTraitReflection(): ClassReflection
	{
		return $this->traitReflection;
	}

	public function getType(): string
	{
		return 'PHPStan_Stmt_InTraitNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
