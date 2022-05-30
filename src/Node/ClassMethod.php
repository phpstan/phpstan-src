<?php declare(strict_types = 1);

namespace PHPStan\Node;

class ClassMethod
{

	public function __construct(
		private \PhpParser\Node\Stmt\ClassMethod $node,
		private bool $isDeclaredInTrait,
	)
	{
	}


	public function getNode(): \PhpParser\Node\Stmt\ClassMethod
	{
		return $this->node;
	}

	public function isDeclaredInTrait(): bool
	{
		return $this->isDeclaredInTrait;
	}

}
