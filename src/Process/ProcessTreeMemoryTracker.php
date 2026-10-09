<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\DependencyInjection\AutowiredService;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use function file_get_contents;
use function max;
use function preg_match;

/**
 * Peak physical memory of the whole process tree - the main process and its
 * workers combined - measured the only way that number exists: as PSS.
 *
 * Per-process figures cannot be added up. A forked worker shares its inherited
 * pages with the main process and every sibling copy-on-write, so each
 * process's RSS counts the same physical page again and a sum multiplies it by
 * the process count. PSS (proportional set size) is the kernel's answer:
 * every physical page is divided among the processes that share it, so the
 * PSS of the tree summed across its processes is the tree's actual footprint
 * at that instant. That covers the turbo arena too - a /dev/shm mapping shared
 * by all workers is split among them the same way. /proc/<pid>/smaps_rollup
 * serves the per-process total without walking the full smaps listing, and
 * reading it needs no privileges for direct children.
 *
 * The kernel keeps no PSS high-water mark, so the peak has to be sampled: a
 * periodic timer on the main process's event loop reads every process's
 * rollup and keeps the largest sum. Two consequences, both making the result
 * an understatement, never an exaggeration: a spike between two samples is
 * missed, and the main process's own late peak - aggregating worker results
 * and saving the result cache, after the workers are gone and the loop with
 * them - is not sampled at all. The second gap is closed in getPeakBytes()
 * with the main process's VmHWM, its lifetime RSS high-water mark: a
 * process's RSS never exceeds the tree's physical footprint at the same
 * instant, so VmHWM is itself a lower bound of the tree's peak and taking
 * the larger of the two bounds only tightens the estimate.
 *
 * On platforms without /proc (Windows, macOS) nothing is sampled and
 * getPeakBytes() returns null; the caller prints nothing.
 */
#[AutowiredService]
final class ProcessTreeMemoryTracker
{

	private const SAMPLE_INTERVAL_SECONDS = 0.5;

	/** @var list<int> */
	private array $childPids = [];

	private ?TimerInterface $timer = null;

	private ?int $sampledPeakBytes = null;

	/**
	 * @param string $filesystemRoot Prefix for every /proc path read, so tests
	 *                               can run against a fixture tree. Empty means
	 *                               the real filesystem.
	 */
	public function __construct(private string $filesystemRoot = '')
	{
	}

	/**
	 * @param list<int> $childPids
	 */
	public function start(LoopInterface $loop, array $childPids): void
	{
		if ($this->readPssBytes('self') === null) {
			return;
		}

		$this->childPids = $childPids;
		$this->sample();
		$this->timer = $loop->addPeriodicTimer(self::SAMPLE_INTERVAL_SECONDS, function (): void {
			$this->sample();
		});
	}

	public function stop(LoopInterface $loop): void
	{
		if ($this->timer === null) {
			return;
		}

		$loop->cancelTimer($this->timer);
		$this->timer = null;

		// the workers quit moments ago at most; what is still resident counts
		$this->sample();
	}

	/**
	 * The largest observed footprint of the whole tree, or null when nothing
	 * was measured - the run was single-process, or the platform has no /proc.
	 */
	public function getPeakBytes(): ?int
	{
		if ($this->sampledPeakBytes === null) {
			return null;
		}

		$ownHighWater = $this->readVmHwmBytes();
		if ($ownHighWater === null) {
			return $this->sampledPeakBytes;
		}

		return max($this->sampledPeakBytes, $ownHighWater);
	}

	public function sample(): void
	{
		$sum = $this->readPssBytes('self');
		if ($sum === null) {
			return;
		}

		foreach ($this->childPids as $pid) {
			// an exited worker's directory is gone; its pages either moved to a
			// surviving sharer's PSS or left the footprint with it
			$pss = $this->readPssBytes((string) $pid);
			if ($pss === null) {
				continue;
			}

			$sum += $pss;
		}

		if ($this->sampledPeakBytes !== null && $sum <= $this->sampledPeakBytes) {
			return;
		}

		$this->sampledPeakBytes = $sum;
	}

	private function readPssBytes(string $pid): ?int
	{
		return $this->readKilobytesLine('/proc/' . $pid . '/smaps_rollup', 'Pss');
	}

	private function readVmHwmBytes(): ?int
	{
		return $this->readKilobytesLine('/proc/self/status', 'VmHWM');
	}

	private function readKilobytesLine(string $path, string $key): ?int
	{
		// the process may have exited, or there is no /proc at all; a warning
		// from a probe would be worse than not knowing
		$contents = @file_get_contents($this->filesystemRoot . $path);
		if ($contents === false) {
			return null;
		}

		if (preg_match('~^' . $key . ':\s+(\d+) kB~m', $contents, $matches) !== 1) {
			return null;
		}

		return (int) $matches[1] * 1024;
	}

}
