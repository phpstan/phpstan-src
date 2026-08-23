<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\ShouldNotHappenException;
use function count;

/**
 * Records every (node, scope) emission of a convergence pass in order. When
 * the pass turns out to be the fixpoint (the final walk's entry scope equals
 * the pass's entry), the final walk is replaced by replaying the recording
 * through the real node callback - the scopes are state-equal to what the
 * repeated walk would emit.
 *
 * Recording appends the raw walk scope - nothing asks about types until a
 * replay happens, so the pass pays no callback-scope construction and no
 * storage binding. replayThrough() wraps each pair the way
 * NodeScopeResolver::callNodeCallback() would have.
 */
final class RecordingNodeCallback
{

	/** @var list<array{Node, Scope}> */
	private array $pairs = [];

	public function __invoke(Node $node, Scope $scope): void
	{
		$this->pairs[] = [$node, $scope];
	}

	public function count(): int
	{
		return count($this->pairs);
	}

	/**
	 * Splices another recording's [$start, $end) segment onto this one - a
	 * convergence pass consuming a subtree copies the subtree's emissions from
	 * the pass that last walked it, so the consuming pass's recording stays
	 * complete. Recordings are append-only, so a tagged segment stays valid
	 * for the lifetime of its recording.
	 */
	public function copyRange(self $source, int $start, int $end): void
	{
		for ($i = $start; $i < $end; $i++) {
			$this->pairs[] = $source->pairs[$i];
		}
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function replayThrough(callable $nodeCallback): void
	{
		foreach ($this->pairs as [$node, $scope]) {
			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}
			$nodeCallback($node, $scope->toNodeCallbackScope());
		}
	}

}
