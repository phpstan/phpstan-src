<?php declare(strict_types = 1);

namespace PHPStan\File;

use DirectoryIterator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function chmod;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function posix_geteuid;
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
		// files watched besides the analysed ones, like config files, usually outside the analysed paths
		mkdir($this->directory . '-config');
		file_put_contents($this->directory . '-config/phpstan.neon', "parameters:\n\tlevel: 5\n");
	}

	#[Override]
	protected function tearDown(): void
	{
		$this->removeDirectory($this->directory);
		$this->removeDirectory($this->directory . '-config');
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
		$this->assertSame([$this->expectedPath('a.php')], $changes->getChangedFiles());
		$this->assertSame([], $changes->getNewFiles());
		$this->assertSame([], $changes->getDeletedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testFileChangedInSubdirectory(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		file_put_contents($this->directory . '/sub/b.php', "<?php\n// b changed\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->expectedPath('sub/b.php')], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testNewFile(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		file_put_contents($this->directory . '/c.php', "<?php\n// c\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->expectedPath('c.php')], $changes->getNewFiles());
		$this->assertSame([], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testNewFileInNewDirectory(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		mkdir($this->directory . '/fresh');
		file_put_contents($this->directory . '/fresh/d.php', "<?php\n// d\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->expectedPath('fresh/d.php')], $changes->getNewFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testDeletedFile(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		unlink($this->directory . '/sub/b.php');
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->expectedPath('sub/b.php')], $changes->getDeletedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testFileThatCannotBeReadIsDeleted(string $kind): void
	{
		// what a file deleted after the finder listed it and before it was hashed, during a git checkout, looks like
		if (PHP_OS_FAMILY === 'Windows' || posix_geteuid() === 0) {
			self::markTestSkipped('A file can be made unreadable only on Unix and not for root.');
		}

		$monitor = $this->createMonitor($kind);
		chmod($this->directory . '/a.php', 0000);
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->expectedPath('a.php')], $changes->getDeletedFiles());

		chmod($this->directory . '/a.php', 0644);
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->expectedPath('a.php')], $changes->getNewFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testDeletedConfigFile(string $kind): void
	{
		$config = $this->directory . '-config/phpstan.neon';
		$monitor = $this->createMonitor($kind, [$config]);
		unlink($config);
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$config], $changes->getDeletedFiles());
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
		$this->assertSame([$this->expectedPath('sub/b.php')], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testFileChangedInDirectoryDeletedAndCreatedAgain(string $kind): void
	{
		$monitor = $this->createMonitor($kind);
		// like switching git branches
		unlink($this->directory . '/sub/b.php');
		rmdir($this->directory . '/sub');
		$this->waitForChanges($monitor);
		mkdir($this->directory . '/sub');
		file_put_contents($this->directory . '/sub/c.php', "<?php\n// c\n");
		$this->waitForChanges($monitor);

		file_put_contents($this->directory . '/sub/c.php', "<?php\n// c changed\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$this->expectedPath('sub/c.php')], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testChangedConfigFile(string $kind): void
	{
		$config = $this->directory . '-config/phpstan.neon';
		$monitor = $this->createMonitor($kind, [$config]);
		file_put_contents($config, "parameters:\n\tlevel: 6\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$config], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testConfigFileChangedTwiceWithinASecondKeepingItsSize(string $kind): void
	{
		$config = $this->directory . '-config/phpstan.neon';
		$monitor = $this->createMonitor($kind, [$config]);
		file_put_contents($config, "parameters:\n\tlevel: 6\n");
		$this->waitForChanges($monitor);
		file_put_contents($config, "parameters:\n\tlevel: 7\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$config], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testConfigFileMissingAtStartAppears(string $kind): void
	{
		$baseline = $this->directory . '-config/baseline.neon';
		$monitor = $this->createMonitor($kind, [$baseline]);
		$this->assertNoChanges($monitor);
		file_put_contents($baseline, "parameters:\n\tignoreErrors: []\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$baseline], $changes->getNewFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testWatchFilesStartsWatchingAFile(string $kind): void
	{
		$config = $this->directory . '-config/phpstan.neon';
		$baseline = $this->directory . '-config/baseline.neon';
		$monitor = $this->createMonitor($kind, [$config]);
		file_put_contents($baseline, "parameters:\n\tignoreErrors: []\n");
		$monitor->watchFiles([$config, $baseline]);
		$this->assertNoChanges($monitor);

		file_put_contents($baseline, "parameters:\n\tignoreErrors: [x]\n");
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$baseline], $changes->getChangedFiles());
	}

	#[DataProvider('dataMonitors')]
	public function testChangeBeforeWatchFilesIsNotLost(string $kind): void
	{
		$config = $this->directory . '-config/phpstan.neon';
		$baseline = $this->directory . '-config/baseline.neon';
		$monitor = $this->createMonitor($kind, [$config]);
		// changed during an analysis, when nobody polls, before the analysis reports its config files
		file_put_contents($config, "parameters:\n\tlevel: 6\n\nincludes:\n\t- baseline.neon\n");
		file_put_contents($baseline, "parameters:\n\tignoreErrors: []\n");
		$monitor->watchFiles([$config, $baseline]);
		$changes = $this->waitForChanges($monitor);
		$this->assertSame([$config], $changes->getChangedFiles());
	}

	/** The monitors report paths normalized by FileHelper, with the platform's directory separator. */
	private function expectedPath(string $relativePath): string
	{
		return (new FileHelper($this->directory))->normalizePath($this->directory . '/' . $relativePath);
	}

	/**
	 * @param string[] $extraFiles
	 */
	private function createMonitor(string $kind, array $extraFiles = []): FileMonitor
	{
		$fileHelper = new FileHelper($this->directory);
		$directoryWalker = new DirectoryWalker(new FileStatSignatures());
		$finder = new FileFinder(new FileExcluder($fileHelper, []), $fileHelper, ['php'], $directoryWalker);
		$hashing = new HashingFileMonitor($finder, $finder, [$this->directory], [$this->directory], [], [], $directoryWalker);

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
			$monitor->initialize($extraFiles);
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
