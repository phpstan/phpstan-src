<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;

// autoTag: false - must not be tagged as a RichParser node visitor
#[AutowiredService(autoTag: false)]
final class ExportedNodeVisitor extends NodeVisitorAbstract
{

	private ?string $fileName = null;

	/** @var RootExportedNode[] */
	private array $currentNodes = [];

	private ExportedNameScopeTracker $nameScopeTracker;

	/**
	 * ExportedNodeVisitor constructor.
	 *
	 */
	public function __construct(private ExportedNodeResolver $exportedNodeResolver)
	{
		$this->nameScopeTracker = new ExportedNameScopeTracker();
	}

	public function reset(string $fileName): void
	{
		$this->fileName = $fileName;
		$this->currentNodes = [];
		$this->nameScopeTracker->reset();
	}

	/**
	 * @return RootExportedNode[]
	 */
	public function getExportedNodes(): array
	{
		return $this->currentNodes;
	}

	#[Override]
	public function enterNode(Node $node): ?int
	{
		if ($this->fileName === null) {
			throw new ShouldNotHappenException();
		}
		$this->nameScopeTracker->enterNode($node);
		$exportedNode = $this->exportedNodeResolver->resolve($node, $this->nameScopeTracker->getNameScope());
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
