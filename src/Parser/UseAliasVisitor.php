<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function count;
use function strtolower;

#[AutowiredService]
final class UseAliasVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isExplicitUseAlias';

	/** @var array<string, string> alias name (original case) keyed by lowercase alias name */
	private array $explicitAliases = [];

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Namespace_) {
			$this->explicitAliases = [];
		}

		if ($node instanceof Use_ && $node->type === Use_::TYPE_NORMAL) {
			foreach ($node->uses as $use) {
				if ($use->alias === null) {
					continue;
				}

				$this->explicitAliases[strtolower($use->alias->name)] = $use->alias->name;
			}
		}

		if ($node instanceof GroupUse) {
			foreach ($node->uses as $use) {
				if ($use->type !== Use_::TYPE_NORMAL && $node->type !== Use_::TYPE_NORMAL) {
					continue;
				}
				if ($use->alias === null) {
					continue;
				}

				$this->explicitAliases[strtolower($use->alias->name)] = $use->alias->name;
			}
		}

		if ($node instanceof Name) {
			$originalName = $node->getAttribute('originalName');
			if ($originalName instanceof Name) {
				$originalParts = $originalName->getParts();
				if (count($originalParts) === 1) {
					$lowerOriginal = strtolower($originalParts[0]);
					if (
						isset($this->explicitAliases[$lowerOriginal])
						&& $this->explicitAliases[$lowerOriginal] === $originalParts[0]
					) {
						$node->setAttribute(self::ATTRIBUTE_NAME, true);
					}
				}
			}
		}

		return null;
	}

}
