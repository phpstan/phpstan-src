<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Records the name scope in effect at every class declaration of a file.
 */
final class NamespaceUsesCollectingVisitor extends NodeVisitorAbstract
{

	/** @var array<string, NamespaceUses> */
	private array $scopes = [];

	public function __construct(private NamespaceUsesTracker $tracker)
	{
	}

	/**
	 * @return array<string, NamespaceUses>
	 */
	public function getScopes(): array
	{
		return $this->scopes;
	}

	#[Override]
	public function enterNode(Node $node): ?int
	{
		$this->tracker->enterNode($node);
		if ($node instanceof Node\Stmt\Class_ && isset($node->namespacedName)) {
			$this->scopes[$node->namespacedName->toString()] = $this->tracker->getNamespaceUses();
		}

		return null;
	}

}
