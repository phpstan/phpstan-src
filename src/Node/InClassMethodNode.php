<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;

/**
 * @api
 * @final
 */
class InClassMethodNode extends Node\Stmt implements VirtualNode
{

	public function __construct(
		private ClassReflection $classReflection,
		private PhpMethodFromParserNodeReflection $methodReflection,
		private Node\Stmt\ClassMethod $originalNode,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

	public function getMethodReflection(): PhpMethodFromParserNodeReflection
	{
		return $this->methodReflection;
	}

	public function getOriginalNode(): Node\Stmt\ClassMethod
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
