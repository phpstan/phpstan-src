<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use Throwable;

/**
 * A parallel analysis worker as seen by ParallelAnalyser / ProcessPool.
 *
 * Implementations differ only in how the worker process comes to life:
 * SpawnedProcess spawns a fresh PHP process via react/child-process,
 * ForkedProcess forks the already-booted main process via pcntl_fork(). Both
 * then speak the same TCP + NDJSON protocol, so request()/quit()/
 * bindConnection() behave identically and live in ProcessBase.
 */
interface Process
{

	/**
	 * $onExit receives the worker's exit code, everything it wrote, and the
	 * signal it was killed by - a worker killed by one has no exit code and
	 * writes nothing, so the signal is all there is to report about it.
	 *
	 * @param callable(mixed[] $json) : void $onData
	 * @param callable(Throwable $exception): void $onError
	 * @param callable(?int $exitCode, string $output, ?int $termSignal) : void $onExit
	 */
	public function start(callable $onData, callable $onError, callable $onExit): void;

	/**
	 * @param mixed[] $data
	 */
	public function request(array $data): void;

	public function quit(): void;

	public function bindConnection(ReadableStreamInterface $out, WritableStreamInterface $in): void;

}
