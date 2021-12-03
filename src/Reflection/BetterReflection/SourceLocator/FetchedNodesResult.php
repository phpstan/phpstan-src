<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

class FetchedNodesResult
{

	/** @var array<string, array<FetchedNode<\PhpParser\Node\Stmt\ClassLike>>> */
	private array $classNodes;

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>> */
	private array $functionNodes;

	/** @var array<int, FetchedNode<\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall>> */
	private array $constantNodes;

	/**
	 * @param array<string, array<FetchedNode<\PhpParser\Node\Stmt\ClassLike>>> $classNodes
	 * @param array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>> $functionNodes
	 * @param array<int, FetchedNode<\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall>> $constantNodes
	 */
	public function __construct(
		array $classNodes,
		array $functionNodes,
		array $constantNodes
	)
	{
		$this->classNodes = $classNodes;
		$this->functionNodes = $functionNodes;
		$this->constantNodes = $constantNodes;
	}

	/**
	 * @return array<string, array<FetchedNode<\PhpParser\Node\Stmt\ClassLike>>>
	 */
	public function getClassNodes(): array
	{
		return $this->classNodes;
	}

	/**
	 * @return array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>>
	 */
	public function getFunctionNodes(): array
	{
		return $this->functionNodes;
	}

	/**
	 * @return array<int, FetchedNode<\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall>>
	 */
	public function getConstantNodes(): array
	{
		return $this->constantNodes;
	}

}
