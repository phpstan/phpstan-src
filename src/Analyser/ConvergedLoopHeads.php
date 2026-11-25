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

	public function warmStart(Node $loop, MutatingScope $entryScope): MutatingScope
	{
		$head = $this->heads[spl_object_id($loop)] ?? null;
		if ($head === null) {
			return $entryScope;
		}

		return $entryScope->mergeWith($head);
	}

	public function record(Node $loop, MutatingScope $head): void
	{
		$this->heads[spl_object_id($loop)] = $head;
	}

}
