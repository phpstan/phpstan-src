<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\ShouldNotHappenException;

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
