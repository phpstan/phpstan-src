<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\ShouldNotHappenException;

class ExportedNodeVisitor extends NodeVisitorAbstract
{

	private ?string $fileName = null;

	/** @var RootExportedNode[] */
	private array $currentNodes = [];

	/**
	 * ExportedNodeVisitor constructor.
	 *
	 */
	public function __construct(private ExportedNodeResolver $exportedNodeResolver)
	{
	}

	public function reset(string $fileName): void
	{
		$this->fileName = $fileName;
		$this->currentNodes = [];
	}

	/**
	 * @return RootExportedNode[]
	 */
	public function getExportedNodes(): array
	{
		return $this->currentNodes;
	}

	public function enterNode(Node $node): ?int
	{
		if ($this->fileName === null) {
			throw new ShouldNotHappenException();
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
			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		return null;
	}

}
