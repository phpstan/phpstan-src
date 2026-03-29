<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\ClassConst;

/**
 * @api
 */
final class ClassConstant
{

	public function __construct(
		private ClassConst $node,
		private bool $isDeclaredInTrait,
	)
	{
	}

	public function getNode(): ClassConst
	{
		return $this->node;
	}

	public function isDeclaredInTrait(): bool
	{
		return $this->isDeclaredInTrait;
	}

}
