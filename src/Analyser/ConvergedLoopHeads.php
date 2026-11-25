<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function spl_object_id;

/**
 * The entry scopes the loops nested in a top-level loop converged to.
 *
 * Every loop converges whenever it is walked, also inside an enclosing loop's
 * convergence passes (Bourdoncle's recursive iteration strategy). The enclosing
 * loop's passes only widen the nested loop's entry, so the nested loop starts
 * converging from the head it reached the previous time instead of from
 * scratch - a nested loop whose head is already stable converges in a single
 * pass, which also stands in for its final walk.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ConvergedLoopHeads.cpp')]
final class ConvergedLoopHeads
{

	/** @var array<int, MutatingScope> */
	private array $heads = [];

	/**
	 * The last entry each loop was warm-started from and the warm entry it gave
	 * with the current head - an enclosing loop's later passes mostly bring the
	 * same entry again.
	 *
	 * @var array<int, array{MutatingScope, MutatingScope}>
	 */
	private array $warmEntries = [];

	public function warmStart(Node $loop, MutatingScope $entryScope): MutatingScope
	{
		$id = spl_object_id($loop);
		$head = $this->heads[$id] ?? null;
		if ($head === null) {
			return $entryScope;
		}

		$warmEntry = $this->warmEntries[$id] ?? null;
		if ($warmEntry !== null && ($warmEntry[0] === $entryScope || $warmEntry[0]->equals($entryScope))) {
			return $warmEntry[1];
		}

		$warmScope = $entryScope->mergeWith($head);
		$this->warmEntries[$id] = [$entryScope, $warmScope];

		return $warmScope;
	}

	public function record(Node $loop, MutatingScope $head): void
	{
		$id = spl_object_id($loop);
		$previous = $this->heads[$id] ?? null;
		// an unchanged head keeps the warm entry derived from it
		if ($previous !== null && ($previous === $head || $previous->equals($head))) {
			return;
		}

		$this->heads[$id] = $head;
		unset($this->warmEntries[$id]);
	}

}
