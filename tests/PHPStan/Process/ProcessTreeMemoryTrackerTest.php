<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Override;
use PHPStan\File\FileWriter;
use PHPUnit\Framework\TestCase;
use React\EventLoop\StreamSelectLoop;
use function dirname;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class ProcessTreeMemoryTrackerTest extends TestCase
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

	public function testPeakIsTheLargestSampledSumOfPssAcrossTheTree(): void
	{
		$root = $this->createFixtureRoot([
			'/proc/self/smaps_rollup' => self::smapsRollup(1000),
			'/proc/101/smaps_rollup' => self::smapsRollup(300),
			'/proc/102/smaps_rollup' => self::smapsRollup(200),
		]);
		$tracker = new ProcessTreeMemoryTracker($root);
		$loop = new StreamSelectLoop();

		$tracker->start($loop, [101, 102]);
		$tracker->stop($loop);

		$this->assertSame(1500 * 1024, $tracker->getPeakBytes());
	}

	public function testPeakKeepsTheLargestSampleNotTheLast(): void
	{
		$root = $this->createFixtureRoot([
			'/proc/self/smaps_rollup' => self::smapsRollup(1000),
			'/proc/101/smaps_rollup' => self::smapsRollup(2000),
		]);
		$tracker = new ProcessTreeMemoryTracker($root);
		$loop = new StreamSelectLoop();

		$tracker->start($loop, [101]);
		FileWriter::write($root . '/proc/101/smaps_rollup', self::smapsRollup(100));
		$tracker->stop($loop);

		$this->assertSame(3000 * 1024, $tracker->getPeakBytes());
	}

	public function testExitedChildIsSkippedInsteadOfSpoilingTheSample(): void
	{
		$root = $this->createFixtureRoot([
			'/proc/self/smaps_rollup' => self::smapsRollup(1000),
			'/proc/101/smaps_rollup' => self::smapsRollup(300),
		]);
		$tracker = new ProcessTreeMemoryTracker($root);
		$loop = new StreamSelectLoop();

		$tracker->start($loop, [101, 999]);
		$tracker->stop($loop);

		$this->assertSame(1300 * 1024, $tracker->getPeakBytes());
	}

	public function testMainProcessHighWaterLiftsAPeakTheSamplerMissed(): void
	{
		// the main process's own peak comes after the workers are gone - result
		// aggregation, result cache save - when nothing samples any more; VmHWM
		// is the kernel's record of it
		$root = $this->createFixtureRoot([
			'/proc/self/smaps_rollup' => self::smapsRollup(1000),
			'/proc/self/status' => "Name:\tphpstan\nVmHWM:\t    5000 kB\nVmRSS:\t    4000 kB\n",
		]);
		$tracker = new ProcessTreeMemoryTracker($root);
		$loop = new StreamSelectLoop();

		$tracker->start($loop, []);
		$tracker->stop($loop);

		$this->assertSame(5000 * 1024, $tracker->getPeakBytes());
	}

	public function testNothingIsReportedWithoutProc(): void
	{
		$tracker = new ProcessTreeMemoryTracker($this->createFixtureRoot([]));
		$loop = new StreamSelectLoop();

		$tracker->start($loop, [101]);
		$tracker->stop($loop);

		$this->assertNull($tracker->getPeakBytes());
	}

	private static function smapsRollup(int $pssKilobytes): string
	{
		return "00400000-7fff9cbe7000 ---p 00000000 00:00 0                          [rollup]\n"
			. "Rss:\t   99999 kB\n"
			. "Pss:\t    " . $pssKilobytes . " kB\n"
			. "Pss_Anon:\t     100 kB\n"
			. "Private_Dirty:\t     100 kB\n";
	}

	/**
	 * @param array<string, string> $files
	 */
	private function createFixtureRoot(array $files): string
	{
		$root = sys_get_temp_dir() . '/phpstan-process-tree-memory-' . uniqid();
		$this->roots[] = $root;

		foreach ($files as $path => $contents) {
			$fullPath = $root . $path;
			$directory = dirname($fullPath);
			if (!is_dir($directory)) {
				mkdir($directory, 0777, true);
			}

			FileWriter::write($fullPath, $contents);
		}

		return $root;
	}

	private static function removeDirectory(string $directory): void
	{
		if (!is_dir($directory)) {
			return;
		}

		$entries = scandir($directory);
		if ($entries === false) {
			return;
		}

		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}

			$path = $directory . '/' . $entry;
			if (is_dir($path)) {
				self::removeDirectory($path);
				continue;
			}

			unlink($path);
		}

		rmdir($directory);
	}

}
