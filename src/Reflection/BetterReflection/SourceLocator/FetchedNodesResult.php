<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;

/**
 * @phpstan-type ClassLikeNode = Node\Stmt\Class_|Node\Stmt\Interface_|Node\Stmt\Trait_|Node\Stmt\Enum_
 */
final class FetchedNodesResult
{

	/**
	 * @param array<string, list<FetchedNode<ClassLikeNode>>> $classNodes
	 * @param array<string, list<FetchedNode<Node\Stmt\Function_>>> $functionNodes
	 * @param array<string, list<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>> $constantNodes
	 */
	public function __construct(
		private array $classNodes,
		private array $functionNodes,
		private array $constantNodes,
	)
	{
	}

	/**
	 * @return array<string, list<FetchedNode<ClassLikeNode>>>
	 */
	public function getClassNodes(): array
	{
		return $this->classNodes;
	}

	/**
	 * @return array<string, list<FetchedNode<Node\Stmt\Function_>>>
	 */
	public function getFunctionNodes(): array
	{
		return $this->functionNodes;
	}

	/**
	 * @return array<string, list<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>>
	 */
	public function getConstantNodes(): array
	{
		return $this->constantNodes;
	}

}
