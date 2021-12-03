<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @template-covariant T of \PhpParser\Node
 */
class FetchedNode
{

	/** @var T */
	private \PhpParser\Node $node;

	private ?\PhpParser\Node\Stmt\Namespace_ $namespace;

	private string $fileName;

	private LocatedSource $locatedSource;

	/**
	 * @param T $node
	 * @param \PhpParser\Node\Stmt\Namespace_|null $namespace
	 * @param string $fileName
	 * @param LocatedSource $locatedSource
	 */
	public function __construct(
		\PhpParser\Node $node,
		?\PhpParser\Node\Stmt\Namespace_ $namespace,
		string $fileName,
		LocatedSource $locatedSource
	)
	{
		$this->node = $node;
		$this->namespace = $namespace;
		$this->fileName = $fileName;
		$this->locatedSource = $locatedSource;
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

	public function getFileName(): string
	{
		return $this->fileName;
	}

	public function getLocatedSource(): LocatedSource
	{
		return $this->locatedSource;
	}

}
