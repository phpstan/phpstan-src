<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Reflection\ClassReflection;

/** @api */
class InClassNode extends Node\Stmt implements VirtualNode
{

	private ClassLike $originalNode;

	private ClassReflection $classReflection;

	public function __construct(ClassLike $originalNode, ClassReflection $classReflection)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
		$this->classReflection = $classReflection;
	}

	public function getOriginalNode(): ClassLike
	{
		return $this->originalNode;
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

	public function getType(): string
	{
		return 'PHPStan_Stmt_InClassNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
