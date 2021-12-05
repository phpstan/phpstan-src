<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ExportedNodeVisitor extends NodeVisitorAbstract
{

	private ExportedNodeResolver $exportedNodeResolver;

	private ?string $fileName = null;

	/** @var ExportedNode[] */
	private array $currentNodes = [];

	/**
	 * ExportedNodeVisitor constructor.
	 *
	 */
	public function __construct(ExportedNodeResolver $exportedNodeResolver)
	{
		$this->exportedNodeResolver = $exportedNodeResolver;
	}

	public function reset(string $fileName): void
	{
		$this->fileName = $fileName;
		$this->currentNodes = [];
	}

	/**
	 * @return ExportedNode[]
	 */
	public function getExportedNodes(): array
	{
		return $this->currentNodes;
	}

	public function enterNode(Node $node): ?int
	{
		if ($this->fileName === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$exportedNode = $this->exportedNodeResolver->resolve($this->fileName, $node);
		if ($exportedNode !== null) {
			$this->currentNodes[] = $exportedNode;
		}

		if (
			$node instanceof Node\Stmt\ClassMethod
			|| $node instanceof Node\Stmt\Function_
			|| $node instanceof Node\Stmt\Trait_
		) {
			return NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		return null;
	}

}
