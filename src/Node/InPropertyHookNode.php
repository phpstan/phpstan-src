<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;

/**
 * @api
 */
final class InPropertyHookNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private ClassReflection $classReflection,
		private PhpMethodFromParserNodeReflection $hookReflection,
		private Node\PropertyHook $originalNode,
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
		return $this->hookReflection;
	}

	public function getOriginalNode(): Node\PropertyHook
	{
		return $this->originalNode;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_InPropertyHookNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
