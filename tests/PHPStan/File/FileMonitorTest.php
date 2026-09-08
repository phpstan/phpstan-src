<?php declare(strict_types = 1);

namespace PHPStan\File;

use DirectoryIterator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function usleep;
use const PHP_OS_FAMILY;

/**
 * Every monitor must report the same changes; the native ones only differ in
 * how long they are allowed to take to notice.
 */
class FileMonitorTest extends TestCase
{

	/** Enough for a native backend to deliver an event; hashing needs none. */
	private const WAIT_ITERATIONS_LIMIT = 200;

	private const WAIT_STEP_MICROSECONDS = 10000;

	private string $directory;

	#[Override]
	protected function setUp(): void
	{
		$this->directory = sys_get_temp_dir() . '/phpstan-file-monitor-' . uniqid();
		mkdir($this->directory . '/sub', 0777, true);
		file_put_contents($this->directory . '/a.php', "<?php\n// a\n");
		file_put_contents($this->directory . '/sub/b.php', "<?php\n// b\n");
	}

	#[Override]
	protected function tearDown(): void
	{
		$this->removeDirectory($this->directory);
	}

	public static function dataMonitors(): iterable
	{
		yield 'hashing' => ['hashing'];

		if (PHP_OS_FAMILY === 'Darwin') {
			yield 'fsevents' => ['fsevents'];
		} elseif (PHP_OS_FAMILY === 'Linux') {
			yield 'inotify' => ['inotify'];
		}
	}

	#[DataProvider('dataMonitors')]
	public function testNothingChanged(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		$this->assertNoChanges($monitor);
	}

	#[DataProvider('dataMonitors')]
	public function testRewritingAFileWithTheSameContentIsNotAChange(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		file_put_contents($this->directory . '/a.php', "<?php\n// a\n");
		$this->assertNoChanges($monitor);
	}

	#[DataProvider('dataMonitors')]
	public function testChangedFile(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		file_put_contents($this->directory . '/a.php', "<?php\n// a changed\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->directory . '/a.php'], $changes->getChangedFiles());
		$this->assertSame([], $changes->getNewFiles());
		$this->assertSame([], $changes->getDeletedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testFileChangedInSubdirectory(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		file_put_contents($this->directory . '/sub/b.php', "<?php\n// b changed\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->directory . '/sub/b.php'], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testNewFile(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		file_put_contents($this->directory . '/c.php', "<?php\n// c\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->directory . '/c.php'], $changes->getNewFiles());
		$this->assertSame([], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testNewFileInNewDirectory(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		mkdir($this->directory . '/fresh');
		file_put_contents($this->directory . '/fresh/d.php', "<?php\n// d\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->directory . '/fresh/d.php'], $changes->getNewFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testDeletedFile(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		unlink($this->directory . '/sub/b.php');
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->directory . '/sub/b.php'], $changes->getDeletedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testChangesAreReportedOnlyOnce(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		file_put_contents($this->directory . '/a.php', "<?php\n// a changed\n");
		$this->waitForChanges($monitor);
		$this->assertNoChanges($monitor);
	}

	#[DataProvider('dataMonitors')]
	public function testSecondChangeAfterTheFirstOne(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		file_put_contents($this->directory . '/a.php', "<?php\n// once\n");
		$this->waitForChanges($monitor);
		file_put_contents($this->directory . '/sub/b.php', "<?php\n// twice\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->directory . '/sub/b.php'], $changes->getChangedFiles());
	}

	private function createMonitor(string $kind): FileMonitor
	{
		$fileHelper = new FileHelper($this->directory);
		$finder = new FileFinder(new FileExcluder($fileHelper, []), $fileHelper, ['php']);
		$hashing = new HashingFileMonitor($finder, $finder, [$this->directory], [$this->directory], [], []);

		if ($kind === 'hashing') {
			$monitor = $hashing;
		} elseif ($kind === 'fsevents') {
			$monitor = new FsEventsFileMonitor($hashing, [$this->directory], [$this->directory], []);
		} elseif ($kind === 'inotify') {
			$monitor = new InotifyFileMonitor($hashing, [$this->directory], [$this->directory], []);
		} else {
			self::fail('Unknown monitor ' . $kind);
		}

		try {
			$monitor->initialize([]);
		} catch (FileMonitorNotSupportedException $e) {
			// FFI disabled, or the kernel refused a watch - the factory would
			// fall back to hashing here, and so does the test
			self::markTestSkipped(sprintf('%s monitor is not supported here: %s', $kind, $e->getMessage()));
		}

		return $monitor;
	}

	private function waitForChanges(FileMonitor $monitor): FileMonitorResult
	{
		for ($i = 0; $i < self::WAIT_ITERATIONS_LIMIT; $i++) {
			$changes = $monitor->getChanges();
			if ($changes->hasAnyChanges()) {
				return $changes;
			}

			usleep(self::WAIT_STEP_MICROSECONDS);
		}

		$this->fail(sprintf('No changes reported within %d ms', self::WAIT_ITERATIONS_LIMIT * self::WAIT_STEP_MICROSECONDS / 1000));
	}

	private function assertNoChanges(FileMonitor $monitor): void
	{
		// a native monitor may still have a queued event; it must not turn into
		// a reported change, so poll a few times rather than only once
		for ($i = 0; $i < 10; $i++) {
			$changes = $monitor->getChanges();
			$this->assertSame([], $changes->getNewFiles());
			$this->assertSame([], $changes->getChangedFiles());
			$this->assertSame([], $changes->getDeletedFiles());
			usleep(self::WAIT_STEP_MICROSECONDS);
		}
	}

	private function removeDirectory(string $directory): void
	{
		if (!is_dir($directory)) {
			return;
		}

		foreach (new DirectoryIterator($directory) as $entry) {
			if ($entry->isDot()) {
				continue;
			}

			if ($entry->isDir()) {
				$this->removeDirectory($entry->getPathname());
				continue;
			}

			unlink($entry->getPathname());
		}

		rmdir($directory);
	}

}
