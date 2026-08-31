<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Override;
use PHPStan\File\FileWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function dirname;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class SystemResourcesTest extends TestCase
{

	/** @var list<string> */
	private array $roots = [];

	#[Override]
	protected function tearDown(): void
	{
		foreach ($this->roots as $root) {
			self::removeDirectory($root);
		}

		$this->roots = [];
	}

	/**
	 * @return iterable<string, array{array<string, string>, int|null}>
	 */
	public static function dataCpuQuota(): iterable
	{
		yield 'v2, quota on the leaf' => [
			[
				'/proc/self/cgroup' => "0::/foo\n",
				'/sys/fs/cgroup/foo/cpu.max' => "200000 100000\n",
			],
			2,
		];

		yield 'v2, quota only on an ancestor' => [
			[
				'/proc/self/cgroup' => "0::/foo/bar\n",
				'/sys/fs/cgroup/foo/cpu.max' => "200000 100000\n",
			],
			2,
		];

		yield 'v2, nested with a tighter ancestor' => [
			[
				'/proc/self/cgroup' => "0::/foo/bar\n",
				'/sys/fs/cgroup/foo/cpu.max' => "100000 100000\n",
				'/sys/fs/cgroup/foo/bar/cpu.max' => "400000 100000\n",
			],
			1,
		];

		yield 'v2, unlimited' => [
			[
				'/proc/self/cgroup' => "0::/foo\n",
				'/sys/fs/cgroup/foo/cpu.max' => "max 100000\n",
			],
			null,
		];

		yield 'v2, cpu controller not enabled at any level' => [
			[
				'/proc/self/cgroup' => "0::/foo\n",
				'/sys/fs/cgroup/foo/memory.max' => "4294967296\n",
			],
			null,
		];

		yield 'v2, quota is not a whole number of cores' => [
			[
				'/proc/self/cgroup' => "0::/foo\n",
				'/sys/fs/cgroup/foo/cpu.max' => "250000 100000\n",
			],
			3,
		];

		yield 'v2, quota below a single core' => [
			[
				'/proc/self/cgroup' => "0::/foo\n",
				'/sys/fs/cgroup/foo/cpu.max' => "50000 100000\n",
			],
			1,
		];

		yield 'v2, malformed' => [
			[
				'/proc/self/cgroup' => "0::/foo\n",
				'/sys/fs/cgroup/foo/cpu.max' => "garbage\n",
			],
			null,
		];

		yield 'v1, quota' => [
			[
				'/proc/self/cgroup' => "4:cpu,cpuacct:/foo\n",
				'/sys/fs/cgroup/cpu/foo/cpu.cfs_quota_us' => "200000\n",
				'/sys/fs/cgroup/cpu/foo/cpu.cfs_period_us' => "100000\n",
			],
			2,
		];

		yield 'v1, unlimited' => [
			[
				'/proc/self/cgroup' => "4:cpu,cpuacct:/foo\n",
				'/sys/fs/cgroup/cpu/foo/cpu.cfs_quota_us' => "-1\n",
				'/sys/fs/cgroup/cpu/foo/cpu.cfs_period_us' => "100000\n",
			],
			null,
		];

		yield 'v1, controller mounted as cpu,cpuacct' => [
			[
				'/proc/self/cgroup' => "4:cpu,cpuacct:/foo\n",
				'/sys/fs/cgroup/cpu,cpuacct/foo/cpu.cfs_quota_us' => "400000\n",
				'/sys/fs/cgroup/cpu,cpuacct/foo/cpu.cfs_period_us' => "100000\n",
			],
			4,
		];

		yield 'v1, a hierarchy whose controller name merely starts with cpu is listed first' => [
			[
				'/proc/self/cgroup' => "7:net_cls,cpuset:/other\n4:cpu,cpuacct:/foo\n",
				'/sys/fs/cgroup/cpu/foo/cpu.cfs_quota_us' => "200000\n",
				'/sys/fs/cgroup/cpu/foo/cpu.cfs_period_us' => "100000\n",
			],
			2,
		];

		yield 'v2, docker container in its own cgroup namespace: the root is the container cgroup' => [
			[
				'/proc/self/cgroup' => "0::/\n",
				'/sys/fs/cgroup/cpu.max' => "200000 100000\n",
			],
			2,
		];

		yield 'v1, docker container in the host cgroup namespace: the mount is rooted at the container cgroup' => [
			[
				'/proc/self/cgroup' => "4:cpu,cpuacct:/docker/0123abcd\n",
				'/sys/fs/cgroup/cpu,cpuacct/cpu.cfs_quota_us' => "400000\n",
				'/sys/fs/cgroup/cpu,cpuacct/cpu.cfs_period_us' => "100000\n",
			],
			4,
		];

		yield 'no cgroup filesystem at all' => [[], null];

		yield 'in the root cgroup, which has no cpu.max' => [
			['/proc/self/cgroup' => "0::/\n"],
			null,
		];
	}

	/**
	 * @param array<string, string> $files
	 */
	#[DataProvider('dataCpuQuota')]
	public function testGetCpuQuota(array $files, ?int $expectedQuota): void
	{
		$resources = new SystemResources($this->createFixtureRoot($files));

		$this->assertSame($expectedQuota, $resources->getCpuQuota());
	}

	public function testUsableCoresNeverExceedDetectedCoresOnThisMachine(): void
	{
		// the important regression: whatever this machine turns out to be, applying
		// its quota must narrow the core count rather than invent capacity or
		// collapse to zero - a probe that guesses low would throttle every run
		$counter = new CpuCoreCounter(null, new SystemResources());

		$this->assertGreaterThanOrEqual(1, $counter->getNumberOfCpuCores());
		$this->assertLessThanOrEqual($counter->getDetectedNumberOfCpuCores(), $counter->getNumberOfCpuCores());
	}

	/**
	 * @param array<string, string> $files
	 */
	private function createFixtureRoot(array $files): string
	{
		$root = sys_get_temp_dir() . '/phpstan-system-resources-' . uniqid();
		$this->roots[] = $root;

		foreach ($files as $path => $contents) {
			$fullPath = $root . $path;
			$directory = dirname($fullPath);
			if (!is_dir($directory)) {
				mkdir($directory, 0777, true);
			}

			FileWriter::write($fullPath, $contents);
		}

		if (!is_dir($root)) {
			mkdir($root, 0777, true);
		}

		return $root;
	}

	private static function removeDirectory(string $directory): void
	{
		if (!is_dir($directory)) {
			return;
		}

		foreach (scandir($directory) ?: [] as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}

			$path = $directory . '/' . $entry;
			if (is_dir($path)) {
				self::removeDirectory($path);
			} else {
				unlink($path);
			}
		}

		rmdir($directory);
	}

}
