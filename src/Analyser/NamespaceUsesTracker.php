<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use function sprintf;
use function strtolower;

/**
 * Keeps the namespace and the use statements seen so far while walking a file's statements.
 *
 * FileTypeMapper drives one while building a name scope map, and so do both places that build
 * exported nodes - ExportedNodeVisitor for a result cache restore, DependencyResolver during the
 * analysis - so that the PHPDoc scope recorded in the result cache and the one computed when
 * checking the cache are produced by the same code as the one PHPDocs are resolved in.
 *
 * The uses are cleared when a namespace is entered rather than when it is left. Namespace blocks
 * cover every statement of a file, so nothing can observe the difference.
 */
final class NamespaceUsesTracker
{

	/** @var non-empty-string|null */
	private ?string $namespace = null;

	/** @var array<string, string> */
	private array $uses = [];

	/** @var array<string, string> */
	private array $constUses = [];

	private ?NamespaceUses $namespaceUses = null;

	public function reset(): void
	{
		$this->namespace = null;
		$this->uses = [];
		$this->constUses = [];
		$this->namespaceUses = null;
	}

	public function enterNode(Node $node): void
	{
		if ($node instanceof Node\Stmt\Namespace_) {
			$this->namespace = $node->name !== null ? $node->name->toString() : null;
			$this->uses = [];
			$this->constUses = [];
		} elseif ($node instanceof Node\Stmt\Use_) {
			if ($node->type === Node\Stmt\Use_::TYPE_NORMAL) {
				foreach ($node->uses as $use) {
					$this->uses[strtolower($use->getAlias()->name)] = $use->name->toString();
				}
			} elseif ($node->type === Node\Stmt\Use_::TYPE_CONSTANT) {
				foreach ($node->uses as $use) {
					$this->constUses[strtolower($use->getAlias()->name)] = $use->name->toString();
				}
			} else {
				return;
			}
		} elseif ($node instanceof Node\Stmt\GroupUse) {
			$prefix = $node->prefix->toString();
			foreach ($node->uses as $use) {
				if ($node->type === Node\Stmt\Use_::TYPE_NORMAL || $use->type === Node\Stmt\Use_::TYPE_NORMAL) {
					$this->uses[strtolower($use->getAlias()->name)] = sprintf('%s\\%s', $prefix, $use->name->toString());
				} elseif ($node->type === Node\Stmt\Use_::TYPE_CONSTANT || $use->type === Node\Stmt\Use_::TYPE_CONSTANT) {
					$this->constUses[strtolower($use->getAlias()->name)] = sprintf('%s\\%s', $prefix, $use->name->toString());
				}
			}
		} else {
			return;
		}

		$this->namespaceUses = null;
	}

	public function getNamespaceUses(): NamespaceUses
	{
		return $this->namespaceUses ??= new NamespaceUses($this->namespace, $this->uses, $this->constUses);
	}

}
