<?php declare(strict_types = 1);

namespace PHPStan\File;

use FilesystemIterator;
use PHPStan\ShouldNotHappenException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function array_keys;
use function count;
use function dirname;
use function file_put_contents;
use function is_dir;
use function is_file;
use function microtime;
use function rtrim;
use function stat;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;
use function uniqid;
use function unlink;
use function usleep;
use const DIRECTORY_SEPARATOR;

/**
 * A {@see FileMonitor} that asks the kernel whether anything changed before
 * doing any work.
 *
 * The kernel only answers the coarse question "was anything touched under these
 * directories". Deciding *which* files that means - honouring the finder's
 * exclude rules, and telling a real edit from a touch that left the content
 * alone - stays with the wrapped {@see HashingFileMonitor}, which runs only on
 * the polls where the kernel said yes. The reported {@see FileMonitorResult} is
 * therefore exactly what the hashing monitor alone would have reported; what
 * changes is that an idle poll no longer reads every analysed file.
 *
 * Directories are the unit of watching: watching files individually would cost
 * a descriptor per file and still miss files that do not exist yet.
 */
abstract class NativeFileMonitor implements FileMonitor
{

	/**
	 * Watching is cheap per poll but not per watch - a non-recursive backend
	 * spends one kernel watch per directory, and the kernel bounds those per
	 * user. A project with more directories than this is served by the hashing
	 * monitor instead of failing halfway through arming.
	 */
	protected const WATCH_LIMIT = 8192;

	/**
	 * An idle poll is a single non-blocking syscall, so the interval can be far
	 * below the hashing monitor's - which is where most of the latency win
	 * comes from, the backends' own delivery delay being a few milliseconds.
	 */
	private const POLL_INTERVAL_SECONDS = 0.05;

	/**
	 * How long {@see self::verifyWatchesDeliver()} waits for the kernel to
	 * report a file it just created. Both backends deliver in single-digit
	 * milliseconds when they work at all, so this only has to be generous
	 * enough for a loaded machine.
	 */
	private const PROBE_TIMEOUT_SECONDS = 0.5;

	private const PROBE_POLL_MICROSECONDS = 2000;

	/** @var array<string, true> */
	private array $watchedDirectories = [];

	/**
	 * Monitored files that lie outside the watched roots - composer.lock, the
	 * config files, the phar PHPStan runs from. There are a few dozen of them
	 * and they are scattered, so they are polled by stat() rather than pulling
	 * their whole parent directory into a recursive watch: the project root is
	 * a common parent, and watching it would make every result cache write look
	 * like a source change.
	 *
	 * stat() is enough because this only has to open the gate - the wrapped
	 * hashing monitor still decides whether the content actually differs.
	 *
	 * @var array<string, array{int, int}>|null path => [mtime, size]
	 */
	private ?array $unwatchedStats = null;

	/**
	 * @param string[] $analysedPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param string[] $scanDirectories
	 */
	public function __construct(
		private HashingFileMonitor $hashingFileMonitor,
		private array $analysedPaths,
		private array $analysedPathsFromConfig,
		private array $scanDirectories,
	)
	{
	}

	/**
	 * @throws FileMonitorNotSupportedException
	 */
	public function initialize(array $filePaths): void
	{
		$this->hashingFileMonitor->initialize($filePaths);
		$this->open();
		$this->arm();
		$this->verifyWatchesDeliver();
	}

	/**
	 * Proves the watches actually fire before anyone relies on them.
	 *
	 * A watch can be registered successfully and then never deliver anything:
	 * inotify reports nothing at all for a Docker Desktop bind mount (the
	 * host's files reach the container through a userspace filesystem), and
	 * the same is true of NFS and other network mounts. Silently watching such
	 * a tree would leave PHPStan Pro looking frozen, which is far worse than
	 * being slow, so a monitor that cannot see a file it created itself is
	 * refused and the caller falls back to hashing.
	 *
	 * @throws FileMonitorNotSupportedException
	 */
	private function verifyWatchesDeliver(): void
	{
		$directory = null;
		$probe = null;
		foreach (array_keys($this->watchedDirectories) as $candidate) {
			$path = $candidate . DIRECTORY_SEPARATOR . '.phpstan-file-monitor-probe-' . uniqid();
			// no .php extension, so the finder never sees it even mid-probe
			if (@file_put_contents($path, '') === false) {
				continue;
			}

			$directory = $candidate;
			$probe = $path;
			break;
		}

		if ($probe === null || $directory === null) {
			// nothing writable to prove anything with
			throw new FileMonitorNotSupportedException();
		}

		$delivered = false;
		$deadline = microtime(true) + self::PROBE_TIMEOUT_SECONDS;
		while (microtime(true) < $deadline) {
			if ($this->drainEvents()) {
				$delivered = true;
				break;
			}

			usleep(self::PROBE_POLL_MICROSECONDS);
		}

		@unlink($probe);
		$this->drainEvents();

		if (!$delivered) {
			throw new FileMonitorNotSupportedException();
		}
	}

	public function getChanges(): FileMonitorResult
	{
		if ($this->unwatchedStats === null) {
			throw new ShouldNotHappenException();
		}

		if (!$this->drainEvents() && !$this->hasUnwatchedChange()) {
			return new FileMonitorResult([], [], []);
		}

		$changes = $this->hashingFileMonitor->getChanges();

		try {
			// the change may have created directories that need watching too
			$this->arm();
		} catch (FileMonitorNotSupportedException) {
			// the project outgrew the watch budget mid-session - the wrapped
			// monitor keeps answering correctly, only without the gate
		}

		return $changes;
	}

	public function getPollInterval(): float
	{
		return self::POLL_INTERVAL_SECONDS;
	}

	/**
	 * @throws FileMonitorNotSupportedException
	 */
	private function arm(): void
	{
		$roots = [];
		foreach ([...$this->analysedPaths, ...$this->analysedPathsFromConfig, ...$this->scanDirectories] as $path) {
			if (!is_dir($path)) {
				continue;
			}

			$roots[$path] = true;
		}

		$unwatchedStats = [];
		foreach ($this->hashingFileMonitor->getMonitoredFiles() as $file) {
			$directory = $this->watchTargetDirectory($file);
			if ($directory !== null && $this->isUnder($directory, $roots)) {
				continue;
			}

			$unwatchedStats[$file] = $this->statOf($file);
		}

		$directories = $this->watchesRecursively() ? array_keys($roots) : $this->expand($roots);
		if (count($directories) > static::WATCH_LIMIT) {
			throw new FileMonitorNotSupportedException();
		}

		foreach ($directories as $directory) {
			if (isset($this->watchedDirectories[$directory])) {
				continue;
			}

			$this->addWatch($directory);
			$this->watchedDirectories[$directory] = true;
		}

		$this->unwatchedStats = $unwatchedStats;
	}

	/**
	 * @return array{int, int}
	 */
	private function statOf(string $file): array
	{
		$stat = @stat($file);

		return $stat === false ? [0, 0] : [$stat['mtime'], $stat['size']];
	}

	/**
	 * Every directory below the roots, empty ones included: a file created in
	 * one of them is a change nobody else would report.
	 *
	 * @param array<string, true> $roots
	 * @return array<string>
	 * @throws FileMonitorNotSupportedException
	 */
	private function expand(array $roots): array
	{
		$directories = $roots;
		foreach (array_keys($roots) as $root) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST,
			);
			foreach ($iterator as $entry) {
				if (!$entry->isDir()) {
					continue;
				}

				$directories[$entry->getPathname()] = true;
				if (count($directories) > static::WATCH_LIMIT) {
					throw new FileMonitorNotSupportedException();
				}
			}
		}

		return array_keys($directories);
	}

	/**
	 * The real directory whose modification would reveal a change to $file, or
	 * null when there is none.
	 *
	 * A file inside a phar cannot change unless the archive does, so the
	 * archive's own directory is what needs watching.
	 */
	private function watchTargetDirectory(string $file): ?string
	{
		if (str_starts_with($file, 'phar://')) {
			$file = substr($file, strlen('phar://'));
			$end = strpos($file, '.phar');
			if ($end === false) {
				return null;
			}

			$file = substr($file, 0, $end + strlen('.phar'));
			if (!is_file($file)) {
				return null;
			}
		}

		$directory = dirname($file);

		return is_dir($directory) ? $directory : null;
	}

	/**
	 * Whether a watch already covers this directory - directly for a recursive
	 * backend, through {@see self::expand()} having watched every directory
	 * below the root for a non-recursive one.
	 *
	 * @param array<string, true> $roots
	 */
	private function isUnder(string $directory, array $roots): bool
	{
		foreach (array_keys($roots) as $root) {
			if ($directory === $root) {
				return true;
			}

			if (str_starts_with($directory, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
				return true;
			}
		}

		return false;
	}

	private function hasUnwatchedChange(): bool
	{
		if ($this->unwatchedStats === null) {
			throw new ShouldNotHappenException();
		}

		foreach ($this->unwatchedStats as $file => $stat) {
			if ($this->statOf($file) !== $stat) {
				return true;
			}
		}

		return false;
	}

	/** Whether one watch covers a whole subtree, or every directory needs its own. */
	abstract protected function watchesRecursively(): bool;

	/**
	 * @throws FileMonitorNotSupportedException
	 */
	abstract protected function open(): void;

	/**
	 * @throws FileMonitorNotSupportedException
	 */
	abstract protected function addWatch(string $directory): void;

	/** Consumes every queued event without blocking. */
	abstract protected function drainEvents(): bool;

}
