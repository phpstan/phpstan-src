<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PhpParser\Node;
use PhpParser\NodeFinder;
use function array_filter;
use function array_key_exists;
use function array_splice;
use function count;
use function spl_object_id;
use function strcasecmp;
use function strtolower;

/**
 * Use statements and class declarations of a namespace, for writing class names into it.
 */
final class ImportScope
{

	/** @var array<string, string> imported class names by lowercase alias */
	public array $uses = [];

	/** @var array<string, true> lowercase first parts of names that resolve against the namespace, and of classes declared in it */
	public array $unqualifiedNames = [];

	/**
	 * @param list<Node\Stmt> $stmts
	 */
	public function __construct(public string $namespace, public array $stmts)
	{
		foreach ($stmts as $stmt) {
			if ($stmt instanceof Node\Stmt\Use_ && $stmt->type === Node\Stmt\Use_::TYPE_NORMAL) {
				foreach ($stmt->uses as $use) {
					$this->uses[strtolower($use->getAlias()->toString())] = $use->name->toString();
				}
			} elseif ($stmt instanceof Node\Stmt\GroupUse) {
				foreach ($stmt->uses as $use) {
					if ($stmt->type !== Node\Stmt\Use_::TYPE_NORMAL && $use->type !== Node\Stmt\Use_::TYPE_NORMAL) {
						continue;
					}
					$this->uses[strtolower($use->getAlias()->toString())] = $stmt->prefix->toString() . '\\' . $use->name->toString();
				}
			} elseif ($stmt instanceof Node\Stmt\ClassLike && $stmt->name !== null) {
				$this->unqualifiedNames[strtolower($stmt->name->toString())] = true;
			}
		}

		$nodeFinder = new NodeFinder();
		$functionAndConstantNames = [];
		foreach ($nodeFinder->find($stmts, static fn (Node $node): bool => $node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\ConstFetch) as $node) {
			if (!$node instanceof Node\Expr\FuncCall && !$node instanceof Node\Expr\ConstFetch) {
				continue;
			}
			$functionAndConstantNames[spl_object_id($node->name)] = true;
		}
		$nonUseStmts = array_filter($stmts, static fn (Node\Stmt $stmt): bool => !$stmt instanceof Node\Stmt\Use_ && !$stmt instanceof Node\Stmt\GroupUse);
		foreach ($nodeFinder->findInstanceOf($nonUseStmts, Node\Name::class) as $name) {
			if (!$name->isUnqualified() && !$name->isQualified()) {
				continue;
			}
			if (array_key_exists(spl_object_id($name), $functionAndConstantNames)) {
				continue;
			}

			$this->unqualifiedNames[strtolower($name->getFirst())] = true;
		}
	}

	/**
	 * Inserts the use statement in alphabetical order among the existing ones.
	 *
	 * @param non-empty-string $className
	 */
	public function addUse(string $className, string $lowerAlias): void
	{
		$this->uses[$lowerAlias] = $className;
		$use = new Node\Stmt\Use_([new Node\UseItem(new Node\Name($className))]);

		$position = null;
		$lastClassUse = null;
		$firstOtherUse = null;
		$lastDeclare = null;
		foreach ($this->stmts as $i => $stmt) {
			if ($stmt instanceof Node\Stmt\Declare_) {
				$lastDeclare = $i;
				continue;
			}
			if (!$stmt instanceof Node\Stmt\Use_) {
				continue;
			}
			if ($stmt->type !== Node\Stmt\Use_::TYPE_NORMAL) {
				$firstOtherUse ??= $i;
				continue;
			}

			$lastClassUse = $i;
			if ($position !== null || count($stmt->uses) !== 1 || strcasecmp($stmt->uses[0]->name->toString(), $className) <= 0) {
				continue;
			}

			$position = $i;
		}

		if ($position === null) {
			if ($lastClassUse !== null) {
				$position = $lastClassUse + 1;
			} elseif ($firstOtherUse !== null) {
				$position = $firstOtherUse;
			} else {
				// the first use statement, separated from the code below it by an empty line
				array_splice($this->stmts, $lastDeclare !== null ? $lastDeclare + 1 : 0, 0, [$use, new Node\Stmt\Nop()]);
				return;
			}
		}

		array_splice($this->stmts, $position, 0, [$use]);
	}

}
