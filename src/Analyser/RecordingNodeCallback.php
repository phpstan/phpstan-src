<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;

/**
 * Records every (node, scope) emission of a convergence pass in order. When
 * the pass turns out to be the fixpoint (the final walk's entry scope equals
 * the pass's entry), the final walk is replaced by replaying the recording
 * through the real node callback - the scopes are state-equal to what the
 * repeated walk would emit.
 *
 * Recording appends the raw walk scope - nothing asks about types until a
 * replay happens, so the pass pays no callback-scope construction and no
 * storage binding. NodeScopeResolver::replayRecording()
 * wraps each pair the way callNodeCallback() would have.
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
	 * @return list<array{Node, Scope}>
	 */
	public function getPairs(): array
	{
		return $this->pairs;
	}

}
