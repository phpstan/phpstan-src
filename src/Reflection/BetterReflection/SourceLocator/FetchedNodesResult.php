<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;

class FetchedNodesResult
{

	/** @var array<string, array<FetchedNode<\PhpParser\Node\Stmt\ClassLike>>> */
	private array $classNodes;

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>> */
	private array $functionNodes;

	/** @var array<int, FetchedNode<\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall>> */
	private array $constantNodes;

	private \PHPStan\BetterReflection\SourceLocator\Located\LocatedSource $locatedSource;

	/**
	 * @param array<string, array<FetchedNode<\PhpParser\Node\Stmt\ClassLike>>> $classNodes
	 * @param array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>> $functionNodes
	 * @param array<int, FetchedNode<\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall>> $constantNodes
	 * @param \PHPStan\BetterReflection\SourceLocator\Located\LocatedSource $locatedSource
	 */
	public function __construct(
		array $classNodes,
		array $functionNodes,
		array $constantNodes,
		LocatedSource $locatedSource
	)
	{
		$this->classNodes = $classNodes;
		$this->functionNodes = $functionNodes;
		$this->constantNodes = $constantNodes;
		$this->locatedSource = $locatedSource;
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

	public function getLocatedSource(): LocatedSource
	{
		return $this->locatedSource;
	}

}
