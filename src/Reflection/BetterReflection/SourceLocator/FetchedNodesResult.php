<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;

final class FetchedNodesResult
{

	/**
	 * @param array<string, array<FetchedNode<Node\Stmt\ClassLike>>> $classNodes
	 * @param array<string, array<FetchedNode<Node\Stmt\Function_>>> $functionNodes
	 * @param array<string, array<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>> $constantNodes
	 */
	public function __construct(
		private array $classNodes,
		private array $functionNodes,
		private array $constantNodes,
	)
	{
	}

	/**
	 * @return array<string, array<FetchedNode<Node\Stmt\ClassLike>>>
	 */
	public function getClassNodes(): array
	{
		return $this->classNodes;
	}

	/**
	 * @return array<string, array<FetchedNode<Node\Stmt\Function_>>>
	 */
	public function getFunctionNodes(): array
	{
		return $this->functionNodes;
	}

	/**
	 * @return array<string, array<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>>
	 */
	public function getConstantNodes(): array
	{
		return $this->constantNodes;
	}

}
