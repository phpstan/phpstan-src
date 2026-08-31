<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\DependencyInjection\AutowiredService;
use function array_key_exists;
use function array_slice;
use function ceil;
use function count;
use function ctype_digit;
use function explode;
use function file_get_contents;
use function implode;
use function in_array;
use function is_file;
use function max;
use function min;
use function str_starts_with;
use function substr;
use function trim;

/**
 * CPU resources the current process may actually use.
 *
 * The count can be constrained by a cgroup. A container limited with
 * `docker run --cpus=2` or a Kubernetes `limits.cpu: 2` gets a CFS bandwidth
 * quota while all of the host's CPUs stay visible and unmasked, so nproc, lscpu
 * and /proc/cpuinfo all report the host's core count - see fidry/cpu-core-counter,
 * which has no cgroup finder at all. Spawning one worker per "detected" core in
 * such a container would result in 64 workers fighting over 2-CPU cores on a CI job.
 *
 * Returns null when no cgroup limits CPU bandwidth (or the platform cannot say),
 * never a guess: the caller falls back to the detected core count.
 */
#[AutowiredService]
final class SystemResources
{

	/**
	 * Which cgroup the process belongs to cannot change while it runs, unlike the values
	 * read out of that cgroup - so the path is memoized and nothing else is.
	 *
	 * @var array<string, string|null>
	 */
	private array $cgroupPaths = [];

	/**
	 * @param string $filesystemRoot Prefix for every /proc and /sys path read, so tests
	 *                               can run against a fixture tree. Empty means the real
	 *                               filesystem.
	 */
	public function __construct(private string $filesystemRoot = '')
	{
	}

	/**
	 * Number of CPU cores the current cgroup's CFS quota allows, or null when no
	 * cgroup limits CPU bandwidth.
	 *
	 * @return positive-int|null
	 */
	public function getCpuQuota(): ?int
	{
		$quotas = [];
		foreach ([$this->getCgroupV2CpuQuota(), $this->getCgroupV1CpuQuota()] as $quota) {
			if ($quota === null) {
				continue;
			}

			$quotas[] = $quota;
		}

		if (count($quotas) === 0) {
			return null;
		}

		// a sub-core quota still lets a single worker run, just throttled
		return max(1, min($quotas));
	}

	/** @return positive-int|null */
	private function getCgroupV2CpuQuota(): ?int
	{
		$cgroupPath = $this->getCgroupPath('');
		if ($cgroupPath === null) {
			return null;
		}

		$quotas = [];
		foreach ($this->getAncestorPaths($cgroupPath) as $path) {
			$cpuMax = $this->readFile('/sys/fs/cgroup' . $path . '/cpu.max');
			if ($cpuMax === null) {
				// the cpu controller is not enabled at this depth - the root cgroup
				// never has the file and a leaf often does not either - which says
				// nothing about the ancestors that may still carry a quota
				continue;
			}

			$parts = explode(' ', trim($cpuMax));
			if (count($parts) !== 2 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])) {
				// "max <period>" is how an unlimited cgroup states it
				continue;
			}

			$period = (int) $parts[1];
			if ($period <= 0) {
				continue;
			}

			$quotas[] = (int) ceil((int) $parts[0] / $period);
		}

		return count($quotas) === 0 ? null : max(1, min($quotas));
	}

	/** @return positive-int|null */
	private function getCgroupV1CpuQuota(): ?int
	{
		$cgroupPath = $this->getCgroupPath('cpu');
		if ($cgroupPath === null) {
			return null;
		}

		$quotas = [];
		foreach ($this->getAncestorPaths($cgroupPath) as $path) {
			foreach (['cpu', 'cpu,cpuacct'] as $controllerDir) {
				$base = '/sys/fs/cgroup/' . $controllerDir . $path;
				$quota = $this->readIntFile($base . '/cpu.cfs_quota_us');
				$period = $this->readIntFile($base . '/cpu.cfs_period_us');
				if ($quota === null || $period === null || $quota <= 0 || $period <= 0) {
					// -1 means unlimited
					continue;
				}

				$quotas[] = (int) ceil($quota / $period);
			}
		}

		return count($quotas) === 0 ? null : max(1, min($quotas));
	}

	/**
	 * The current process' path within a cgroup hierarchy, or null when it is not in
	 * one. An empty controller asks for the v2 unified hierarchy.
	 */
	private function getCgroupPath(string $controller): ?string
	{
		if (array_key_exists($controller, $this->cgroupPaths)) {
			return $this->cgroupPaths[$controller];
		}

		return $this->cgroupPaths[$controller] = $this->findCgroupPath($controller);
	}

	private function findCgroupPath(string $controller): ?string
	{
		$contents = $this->readFile('/proc/self/cgroup');
		if ($contents === null) {
			return null;
		}

		foreach (explode("\n", $contents) as $line) {
			// hierarchy-ID:controller-list:path
			$parts = explode(':', trim($line), 3);
			if (count($parts) !== 3) {
				continue;
			}

			[, $controllers, $path] = $parts;
			if ($controller === '') {
				if ($controllers !== '') {
					continue;
				}
			} elseif (!in_array($controller, explode(',', $controllers), true)) {
				// a substring match would take cpuset or cpuacct for cpu
				continue;
			}

			return $path === '/' ? '' : $path;
		}

		return null;
	}

	/**
	 * The cgroup's own path and every ancestor up to the root, because a limit set on
	 * an ancestor binds the leaf just as tightly - Kubernetes puts the pod's quota on
	 * the pod slice, not on the container's own cgroup.
	 *
	 * @return list<string>
	 */
	private function getAncestorPaths(string $path): array
	{
		$segments = [];
		foreach (explode('/', $path) as $segment) {
			if ($segment === '') {
				continue;
			}

			$segments[] = $segment;
		}

		$paths = [''];
		for ($i = 1; $i <= count($segments); $i++) {
			$paths[] = '/' . implode('/', array_slice($segments, 0, $i));
		}

		return $paths;
	}

	private function readIntFile(string $path): ?int
	{
		$contents = $this->readFile($path);
		if ($contents === null) {
			return null;
		}

		$contents = trim($contents);
		$negative = str_starts_with($contents, '-');
		$digits = $negative ? substr($contents, 1) : $contents;
		if ($digits === '' || !ctype_digit($digits)) {
			return null;
		}

		return $negative ? -(int) $digits : (int) $digits;
	}

	private function readFile(string $path): ?string
	{
		$path = $this->filesystemRoot . $path;

		// container filesystems routinely have these present but unreadable, and a
		// warning from a probe would be worse than not knowing
		if (!@is_file($path)) {
			return null;
		}

		$contents = @file_get_contents($path);

		return $contents === false ? null : $contents;
	}

}
