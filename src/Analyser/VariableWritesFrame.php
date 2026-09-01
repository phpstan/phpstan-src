<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Node\VariableWritesNode;
use function array_keys;
use function in_array;
use function spl_object_id;

/**
 * Write sites of one function-like body, which of them were read at a sink,
 * and which flow into which other writes.
 *
 * Immutable and persistent: every transition returns a new instance, or $this
 * when nothing changes, so the hot read path allocates nothing. The engine
 * (NodeScopeResolver) holds the current frame and swaps it after a transition.
 *
 * A write site is identified by its target node, so re-walks of the same body
 * (loop convergence, closure passes) map onto the same write.
 *
 * A read either happens at a sink (a call argument, a condition, a return...)
 * and marks the reaching writes as read, or it computes the value of another
 * write (the right side of `$b = $a + 1`) and records that the target write
 * depends on the reaching writes - a write is used iff it reaches a sink
 * directly or through such dependencies (VariableWritesNode::isUsed()).
 *
 * @internal
 */
final class VariableWritesFrame
{

	/** A read of the variable itself: every reaching write of it. */
	public const READ_WHOLE = 1;

	/** The container an offset write or unset modifies: the whole-variable writes only. */
	public const READ_CONTAINER = 2;

	/** A read of one offset: the whole-variable writes, the writes of that offset and of unknown offsets. */
	public const READ_OFFSET = 3;

	/**
	 * @param array<int, VariableWrite> $writes id => write
	 * @param array<int, int> $idsByNode spl_object_id(target node) => id
	 * @param array<string, list<int>> $idsByName
	 * @param array<int, true> $readIds
	 * @param array<int, array<int, true>> $dependencies target id => ids of the writes its value is computed from
	 * @param array<int, list<int>> $childIds whole-variable write id => ids of the literal items it assigned
	 * @param array<string, true> $untrackedNames
	 */
	private function __construct(
		private array $writes,
		private array $idsByNode,
		private array $idsByName,
		private array $readIds,
		private array $dependencies,
		private array $childIds,
		private array $untrackedNames,
		private bool $opaque,
	)
	{
	}

	public static function create(): self
	{
		return new self([], [], [], [], [], [], [], false);
	}

	/**
	 * @param VariableWrite::KIND_* $kind
	 * @param int|string|null $offset
	 */
	public function withWrite(Node $node, string $name, int $kind, int $id, bool $isOffsetWrite = false, $offset = null, ?int $parentId = null): self
	{
		if (
			$name === 'this'
			|| in_array($name, Scope::SUPERGLOBAL_VARIABLES, true)
			|| isset($this->untrackedNames[$name])
		) {
			return $this;
		}
		$nodeId = spl_object_id($node);
		if (isset($this->idsByNode[$nodeId])) {
			return $this;
		}

		$writes = $this->writes;
		$writes[$id] = new VariableWrite($name, $node, $id, $kind, $isOffsetWrite, $offset, $parentId);
		$idsByNode = $this->idsByNode;
		$idsByNode[$nodeId] = $id;
		$idsByName = $this->idsByName;
		$idsByName[$name][] = $id;
		$childIds = $this->childIds;
		if ($parentId !== null) {
			$childIds[$parentId][] = $id;
		}

		return new self($writes, $idsByNode, $idsByName, $this->readIds, $this->dependencies, $childIds, $this->untrackedNames, $this->opaque);
	}

	public function getWrite(Node $node): ?VariableWrite
	{
		$id = $this->idsByNode[spl_object_id($node)] ?? null;
		if ($id === null) {
			return null;
		}

		return $this->writes[$id];
	}

	/**
	 * Markers of every write of the variable registered so far - the set a
	 * whole-variable write (or unset) of it kills.
	 *
	 * @return list<Expr>
	 */
	public function getMarkerExprsForName(string $name): array
	{
		$exprs = [];
		foreach ($this->idsByName[$name] ?? [] as $id) {
			$exprs[] = $this->writes[$id]->getMarkerExpr();
		}

		return $exprs;
	}

	/**
	 * Markers of the writes of one constant offset of the variable - the set a
	 * write (or unset) of that offset kills.
	 *
	 * @param int|string $offset
	 * @return list<Expr>
	 */
	public function getMarkerExprsForOffset(string $name, $offset): array
	{
		$exprs = [];
		foreach ($this->idsByName[$name] ?? [] as $id) {
			$write = $this->writes[$id];
			if (!$write->isOffsetWrite() || $write->getOffset() !== $offset) {
				continue;
			}
			$exprs[] = $write->getMarkerExpr();
		}

		return $exprs;
	}

	/**
	 * The markers a write plants: its own and those of the literal items it
	 * assigns.
	 *
	 * @return list<Expr>
	 */
	public function getMarkerExprsToPlant(VariableWrite $write): array
	{
		$exprs = [$write->getMarkerExpr()];
		foreach ($this->childIds[$write->getId()] ?? [] as $childId) {
			$exprs[] = $this->writes[$childId]->getMarkerExpr();
		}

		return $exprs;
	}

	/**
	 * Records a read of the variable on $scope. Without a value-flow target the
	 * read is a sink: every selected write whose marker still reaches $scope
	 * has been read. With a target the selected reaching writes are the ones
	 * the target's value is computed from.
	 *
	 * @param self::READ_* $selection
	 * @param int|string|null $offset the offset read for READ_OFFSET
	 */
	public function withUsesFor(string $name, MutatingScope $scope, int $selection, $offset, ?VariableWrite $valueFlowTarget): self
	{
		$ids = $this->idsByName[$name] ?? null;
		if ($ids === null) {
			return $this;
		}
		$targetId = $valueFlowTarget !== null ? $valueFlowTarget->getId() : null;
		$readIds = null;
		$dependencies = null;
		foreach ($ids as $id) {
			$write = $this->writes[$id];
			if ($selection === self::READ_CONTAINER) {
				if ($write->isOffsetWrite()) {
					continue;
				}
			} elseif ($selection === self::READ_OFFSET) {
				if ($write->isOffsetWrite() && $write->getOffset() !== null && $write->getOffset() !== $offset) {
					continue;
				}
			}
			if ($targetId === null) {
				if (isset($this->readIds[$id])) {
					continue;
				}
			} elseif ($id === $targetId || isset($this->dependencies[$targetId][$id])) {
				continue;
			}
			if ($scope->hasExpressionType($write->getMarkerExpr())->no()) {
				continue;
			}
			if ($targetId === null) {
				if ($readIds === null) {
					$readIds = $this->readIds;
				}
				$readIds[$id] = true;
			} else {
				if ($dependencies === null) {
					$dependencies = $this->dependencies;
				}
				$dependencies[$targetId][$id] = true;
			}
		}
		if ($readIds === null && $dependencies === null) {
			return $this;
		}

		return new self($this->writes, $this->idsByNode, $this->idsByName, $readIds ?? $this->readIds, $dependencies ?? $this->dependencies, $this->childIds, $this->untrackedNames, $this->opaque);
	}

	/**
	 * Records a read of every variable (get_defined_vars(), include, eval, $$name).
	 */
	public function withAllReachingRead(MutatingScope $scope): self
	{
		$readIds = null;
		foreach ($this->writes as $id => $write) {
			if (isset($this->readIds[$id])) {
				continue;
			}
			if ($scope->hasExpressionType($write->getMarkerExpr())->no()) {
				continue;
			}
			if ($readIds === null) {
				$readIds = $this->readIds;
			}
			$readIds[$id] = true;
		}
		if ($readIds === null) {
			return $this;
		}

		return new self($this->writes, $this->idsByNode, $this->idsByName, $readIds, $this->dependencies, $this->childIds, $this->untrackedNames, $this->opaque);
	}

	/**
	 * The value of $from also flows into $to (`$a = $b = $c + 1`: what $b is
	 * computed from, $a is computed from as well).
	 */
	public function withDependenciesCopied(VariableWrite $to, VariableWrite $from): self
	{
		$fromIds = $this->dependencies[$from->getId()] ?? null;
		if ($fromIds === null) {
			return $this;
		}
		$toId = $to->getId();
		$dependencies = null;
		foreach (array_keys($fromIds) as $id) {
			if ($id === $toId || isset($this->dependencies[$toId][$id])) {
				continue;
			}
			if ($dependencies === null) {
				$dependencies = $this->dependencies;
			}
			$dependencies[$toId][$id] = true;
		}
		if ($dependencies === null) {
			return $this;
		}

		return new self($this->writes, $this->idsByNode, $this->idsByName, $this->readIds, $dependencies, $this->childIds, $this->untrackedNames, $this->opaque);
	}

	public function withUntracked(string $name): self
	{
		if (isset($this->untrackedNames[$name])) {
			return $this;
		}
		$untrackedNames = $this->untrackedNames;
		$untrackedNames[$name] = true;

		return new self($this->writes, $this->idsByNode, $this->idsByName, $this->readIds, $this->dependencies, $this->childIds, $untrackedNames, $this->opaque);
	}

	public function withOpaque(): self
	{
		if ($this->opaque) {
			return $this;
		}

		return new self($this->writes, $this->idsByNode, $this->idsByName, $this->readIds, $this->dependencies, $this->childIds, $this->untrackedNames, true);
	}

	public function createNode(Node\FunctionLike $functionLike): VariableWritesNode
	{
		return new VariableWritesNode($functionLike, $this->writes, $this->readIds, $this->dependencies, $this->untrackedNames, $this->opaque);
	}

}
