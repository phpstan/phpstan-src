<?php declare(strict_types = 1);

namespace PHPStan\Process;

use React\Promise\PromiseInterface;

/**
 * A PHPStan Pro analysis worker as seen by FixerApplication.
 *
 * Implementations differ only in how the worker process comes to life:
 * SpawnedProcessPromise spawns a fresh PHP process via react/child-process,
 * ForkedProcessPromise forks the already-booted main process via pcntl_fork().
 * Both yield a promise that resolves on success and rejects with
 * ProcessCrashedException / ProcessCanceledException otherwise.
 */
interface ProcessPromise
{

	/**
	 * @return PromiseInterface<string>
	 */
	public function run(): PromiseInterface;

}
