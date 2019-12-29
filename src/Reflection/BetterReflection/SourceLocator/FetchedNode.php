<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

/**
 * @template-covariant T of \PhpParser\Node
 */
class FetchedNode
{

	/** @var T */
	private $node;

	/** @var \PhpParser\Node\Stmt\Namespace_|null */
	private $namespace;

	/**
	 * @param T $node
	 * @param \PhpParser\Node\Stmt\Namespace_|null $namespace
	 */
	public function __construct(
		\PhpParser\Node $node,
		?\PhpParser\Node\Stmt\Namespace_ $namespace
	)
	{
		$this->node = $node;
		$this->namespace = $namespace;
	}

	/**
	 * @return T
	 */
	public function getNode(): \PhpParser\Node
	{
		return $this->node;
	}

	public function getNamespace(): ?\PhpParser\Node\Stmt\Namespace_
	{
		return $this->namespace;
	}

}
