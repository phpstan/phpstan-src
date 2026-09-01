<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Node\Variable\VariableWrite;
use function array_keys;
use function array_pop;
use function array_values;
use function count;

/**
 * All local-variable write sites of a function-like body, with the set of
 * those whose written value is used: read at a sink on some path afterwards,
 * or computed into another write that is.
 *
 * Emitted right after the body's ReturnStatementsNode. Arrow functions have no
 * node of their own - their writes belong to the enclosing function-like.
 */
final class VariableWritesNode extends NodeAbstract implements VirtualNode
{

	/** @var array<int, true> */
	private array $usedWriteIds;

	/**
	 * @param array<int, VariableWrite> $writes id => write
	 * @param array<int, true> $readWriteIds
	 * @param array<int, array<int, true>> $dependencies target id => ids of the writes its value is computed from
	 * @param array<string, true> $untrackedVariableNames
	 */
	public function __construct(
		Node\FunctionLike $functionLike,
		private array $writes,
		array $readWriteIds,
		array $dependencies,
		private array $untrackedVariableNames,
		private bool $opaque,
	)
	{
		parent::__construct($functionLike->getAttributes());
		// a write that reaches a sink is used, and so is every write its value
		// was computed from - transitively
		$used = $readWriteIds;
		$stack = array_keys($readWriteIds);
		while (count($stack) > 0) {
			$id = array_pop($stack);
			foreach (array_keys($dependencies[$id] ?? []) as $dependencyId) {
				if (isset($used[$dependencyId])) {
					continue;
				}
				$used[$dependencyId] = true;
				$stack[] = $dependencyId;
			}
		}
		$this->usedWriteIds = $used;
	}

	/**
	 * @return list<VariableWrite>
	 */
	public function getWrites(): array
	{
		return array_values($this->writes);
	}

	public function getWrite(int $id): ?VariableWrite
	{
		return $this->writes[$id] ?? null;
	}

	/**
	 * Whether the written value reaches a sink on some path, directly or
	 * through the writes it was computed into.
	 */
	public function isUsed(VariableWrite $write): bool
	{
		return isset($this->usedWriteIds[$write->getId()]);
	}

	/**
	 * Variables whose writes escape the body (by-ref parameters and uses,
	 * global/static variables, reference aliases) - every write counts as used.
	 */
	public function isUntracked(string $variableName): bool
	{
		return isset($this->untrackedVariableNames[$variableName]);
	}

	/**
	 * The body contains a construct (goto) that defeats reaching-write tracking.
	 */
	public function isOpaque(): bool
	{
		return $this->opaque;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_VariableWritesNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
