<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;

final class ClassNameNodePair
{

	public function __construct(private string $className, private Node $node)
	{
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function getNode(): Node
	{
		return $this->node;
	}

}
