<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use function array_key_exists;
use function array_keys;
use function closedir;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function getmypid;
use function implode;
use function is_array;
use function is_dir;
use function lstat;
use function mkdir;
use function opendir;
use function preg_match;
use function readdir;
use function rename;
use function rtrim;
use function serialize;
use function sprintf;
use function stat;
use function str_contains;
use function str_starts_with;
use function substr;
use function unlink;
use function unserialize;
use const DIRECTORY_SEPARATOR;

/**
 * The raw filesystem walk behind FileFinder.
 *
 * The analyse and the scan FileFinder differ only in their FileExcluder, so an analysis walks the
 * same directories twice: once to build the list of analysed files, and once for the result cache
 * metadata, which records the files that are scanned but not analysed. walkCached() shares that
 * walk between the two - both happen before the analysis starts, on a file set that is snapshotted
 * for the whole run anyway.
 *
 * FileMonitor detects changes by re-running the finder and must see the filesystem as it is now,
 * so it walks uncached and clears the shared walks before each check.
 *
 * Reading the directories is what a walk costs - on a tree the size of Drupal core (28k directories)
 * about a second, every run, in the main process and again in the worker that builds the symbol
 * index. What a directory contains changes only when an entry is added, removed or renamed in it,
 * so the listings are kept in tmpDir and a directory whose stat signature still matches (see
 * FileStatSignatures) is not read again. The same technique is behind git's untracked cache.
 *
 * The walk yields what Symfony Finder yields for files()->name()->followLinks() with its default
 * ignores - dot files and VCS directories left out, symlinks followed, files in readdir order - and
 * falls back to Finder itself for anything it does not handle the same way (Windows paths, stream
 * wrappers, unreadable directories), so errors surface exactly as they did.
 */
#[AutowiredService]
final class DirectoryWalker
{

	private const LISTINGS_FORMAT = 'directoryListings-v2';

	/** The directories Finder's ignoreVCS() leaves out; the ones starting with a dot are left out anyway. */
	private const VCS_DIRECTORIES = ['_svn' => true, 'CVS' => true, '_darcs' => true];

	/** @var array<string, list<string>> */
	private array $cachedWalks = [];

	/**
	 * Directory path => [stat signature, entries]. The entries are the directory's names in
	 * readdir order, each prefixed by d (directory), f (anything else - Finder's files() only leaves
	 * out directories, so a broken symlink is a file too) or l (symlink - resolved on every walk,
	 * because its target can change without this directory changing), joined by NUL.
	 *
	 * @var array<string, array{string, string}>|null
	 */
	private ?array $listings = null;

	private bool $listingsChanged = false;

	/**
	 * @param string $tmpDir where the listings are kept between runs, nowhere when empty
	 */
	public function __construct(
		private FileStatSignatures $fileStatSignatures,
		#[AutowiredParameter]
		private string $tmpDir = '',
	)
	{
	}

	/**
	 * @param string[] $fileExtensions
	 * @return list<string>
	 */
	public function walk(string $directory, array $fileExtensions): array
	{
		$files = $this->walkWithListings($directory, $fileExtensions);
		if ($files !== null) {
			return $files;
		}

		$finder = new Finder();
		$finder->followLinks();

		$files = [];
		foreach ($finder->files()->name('*.{' . implode(',', $fileExtensions) . '}')->in($directory) as $fileInfo) {
			$files[] = $fileInfo->getPathname();
		}

		return $files;
	}

	/**
	 * @param string[] $fileExtensions
	 * @return list<string>
	 */
	public function walkCached(string $directory, array $fileExtensions): array
	{
		$key = $directory . "\n" . implode(',', $fileExtensions);
		if (array_key_exists($key, $this->cachedWalks)) {
			return $this->cachedWalks[$key];
		}

		return $this->cachedWalks[$key] = $this->walk($directory, $fileExtensions);
	}

	public function clearCachedWalks(): void
	{
		$this->cachedWalks = [];
	}

	/**
	 * @param string[] $fileExtensions
	 * @return list<string>|null null when Finder has to do the walk
	 */
	private function walkWithListings(string $directory, array $fileExtensions): ?array
	{
		if (DIRECTORY_SEPARATOR !== '/' || str_contains($directory, '://')) {
			return null;
		}

		// what Finder's normalizeDir() does, the filesystem root aside
		$directory = rtrim($directory, '/');
		if ($directory === '') {
			return null;
		}

		$this->loadListings();

		$files = [];
		$visited = [];
		if (!$this->walkDirectory($directory, Glob::toRegex('*.{' . implode(',', $fileExtensions) . '}'), $this->fileStatSignatures->begin(), $files, $visited)) {
			return null;
		}

		$this->saveListings($directory, $visited);

		return $files;
	}

	/**
	 * @param list<string> $files
	 * @param array<string, true> $visited
	 */
	private function walkDirectory(string $directory, string $pattern, FileStatSignatureReader $signatures, array &$files, array &$visited): bool
	{
		$stat = @stat($directory);
		if ($stat === false) {
			return false;
		}

		$visited[$directory] = true;
		$signature = $signatures->fromStat($stat);
		$listing = $this->listings[$directory] ?? null;
		if ($signature !== null && $listing !== null && $listing[0] === $signature) {
			$entries = $listing[1];
		} else {
			$entries = $this->readDirectory($directory);
			if ($entries === null) {
				return false;
			}

			if ($signature !== null) {
				$this->listings[$directory] = [$signature, $entries];
			} else {
				unset($this->listings[$directory]);
			}
			$this->listingsChanged = true;
		}

		if ($entries === '') {
			return true;
		}

		foreach (explode("\0", $entries) as $entry) {
			$type = $entry[0];
			$name = substr($entry, 1);
			$path = $directory . '/' . $name;
			if ($type === 'l') {
				if (is_dir($path)) {
					if (isset(self::VCS_DIRECTORIES[$name])) {
						continue;
					}
					$type = 'd';
				} else {
					$type = 'f';
				}
			}

			if ($type === 'd') {
				if (!$this->walkDirectory($path, $pattern, $signatures, $files, $visited)) {
					return false;
				}

				continue;
			}

			if (preg_match($pattern, $name) !== 1) {
				continue;
			}

			$files[] = $path;
		}

		return true;
	}

	private function readDirectory(string $directory): ?string
	{
		$handle = @opendir($directory);
		if ($handle === false) {
			return null;
		}

		$entries = [];
		while (($name = readdir($handle)) !== false) {
			// also skips . and .. - Finder leaves out everything starting with a dot
			if (str_starts_with($name, '.')) {
				continue;
			}

			$stat = @lstat($directory . '/' . $name);
			if ($stat === false) {
				continue;
			}

			$type = $stat['mode'] & 0170000;
			if ($type === 0120000) {
				$entries[] = 'l' . $name;
			} elseif ($type === 0040000) {
				if (isset(self::VCS_DIRECTORIES[$name])) {
					continue;
				}
				$entries[] = 'd' . $name;
			} else {
				$entries[] = 'f' . $name;
			}
		}
		closedir($handle);

		return implode("\0", $entries);
	}

	private function getListingsFile(): ?string
	{
		if ($this->tmpDir === '') {
			return null;
		}

		return $this->tmpDir . '/cache/directory-listings.bin';
	}

	private function loadListings(): void
	{
		if ($this->listings !== null) {
			return;
		}

		$this->listings = [];
		$file = $this->getListingsFile();
		if ($file === null) {
			return;
		}

		$contents = @file_get_contents($file);
		if ($contents === false) {
			return;
		}

		$data = @unserialize($contents, ['allowed_classes' => false]);
		if (!is_array($data) || ($data['format'] ?? null) !== self::LISTINGS_FORMAT || !is_array($data['listings'] ?? null)) {
			return;
		}

		/** @var array<string, array{string, string}> $listings */
		$listings = $data['listings'];
		$this->listings = $listings;
	}

	/**
	 * @param array<string, true> $visited
	 */
	private function saveListings(string $root, array $visited): void
	{
		if (!$this->listingsChanged || $this->listings === null) {
			return;
		}

		// A directory under the walked root that the walk did not reach is gone, or no longer
		// reachable - either way its listing is not going to be read again.
		foreach (array_keys($this->listings) as $directory) {
			if (isset($visited[$directory]) || !str_starts_with($directory, $root . '/')) {
				continue;
			}

			unset($this->listings[$directory]);
		}

		$this->listingsChanged = false;
		$file = $this->getListingsFile();
		if ($file === null) {
			return;
		}

		$cacheDirectory = $this->tmpDir . '/cache';
		if (!is_dir($cacheDirectory) && !@mkdir($cacheDirectory, 0777, true) && !is_dir($cacheDirectory)) {
			return;
		}

		// written next to the final path and renamed into place, so that a concurrent run reads
		// either the old listings or the new ones
		$pid = getmypid();
		$temporaryFile = sprintf('%s.%s.tmp', $file, $pid === false ? 'x' : $pid);
		if (@file_put_contents($temporaryFile, serialize(['format' => self::LISTINGS_FORMAT, 'listings' => $this->listings])) === false) {
			return;
		}

		if (@rename($temporaryFile, $file)) {
			return;
		}

		@unlink($temporaryFile);
	}

}
