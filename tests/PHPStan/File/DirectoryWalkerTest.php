<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Testing\PHPStanTestCase;
use Symfony\Component\Finder\Finder;
use function clearstatcache;
use function file_put_contents;
use function implode;
use function max;
use function mkdir;
use function rename;
use function rmdir;
use function stat;
use function symlink;
use function sys_get_temp_dir;
use function time;
use function uniqid;
use function unlink;
use function usleep;
use const DIRECTORY_SEPARATOR;

final class DirectoryWalkerTest extends PHPStanTestCase
{

	private string $directory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->directory = sys_get_temp_dir() . '/' . uniqid('phpstan-walker-', true);
		mkdir($this->directory);
		file_put_contents($this->directory . '/first.php', '<?php');
	}

	public function testWalkAlwaysSeesTheCurrentState(): void
	{
		$walker = new DirectoryWalker();

		$beforeAdding = $walker->walk($this->directory, ['php']);
		file_put_contents($this->directory . '/second.php', '<?php');
		$afterAdding = $walker->walk($this->directory, ['php']);

		$this->assertCount(1, $beforeAdding);
		$this->assertCount(2, $afterAdding);
	}

	public function testWalkCachedIsSharedBetweenCalls(): void
	{
		$walker = new DirectoryWalker();

		$firstWalk = $walker->walkCached($this->directory, ['php']);
		file_put_contents($this->directory . '/second.php', '<?php');
		// The analyse and the scan FileFinder must see one and the same file set - the analysis
		// works off a snapshot taken before it starts.
		$secondWalk = $walker->walkCached($this->directory, ['php']);
		$walker->clearCachedWalks();
		$walkAfterClearing = $walker->walkCached($this->directory, ['php']);

		$this->assertCount(1, $firstWalk);
		$this->assertCount(1, $secondWalk);
		$this->assertCount(2, $walkAfterClearing);
	}

	public function testCachedWalksAreKeyedByExtensions(): void
	{
		$walker = new DirectoryWalker();
		file_put_contents($this->directory . '/notes.txt', 'x');

		$phpFiles = $walker->walkCached($this->directory, ['php']);
		$textFiles = $walker->walkCached($this->directory, ['txt']);

		$this->assertCount(1, $phpFiles);
		$this->assertCount(1, $textFiles);
	}

	public function testWalkYieldsWhatFinderYields(): void
	{
		$tmpDir = $this->createTree();

		$expected = $this->walkWithFinder($this->directory, ['php', 'sh', '']);
		$fresh = (new DirectoryWalker($tmpDir))->walk($this->directory, ['php', 'sh', '']);
		$this->waitUntilTheTreeIsInThePast();
		// stores the listings, now that they are no longer racy
		(new DirectoryWalker($tmpDir))->walk($this->directory, ['php', 'sh', '']);
		$fromListings = (new DirectoryWalker($tmpDir))->walk($this->directory, ['php', 'sh', '']);

		$this->assertNotSame([], $expected);
		$this->assertSame($expected, $fresh);
		$this->assertSame($expected, $fromListings);
		$this->assertFileExists($tmpDir . '/cache/directory-listings.bin');
	}

	public function testListingsSeeAddedAndRemovedFiles(): void
	{
		$tmpDir = $this->createTree();
		$this->waitUntilTheTreeIsInThePast();
		(new DirectoryWalker($tmpDir))->walk($this->directory, ['php']);

		file_put_contents($this->directory . '/src/Added.php', '<?php');
		unlink($this->directory . '/src/Nested/Deep.php');
		rename($this->directory . '/src/Foo.php', $this->directory . '/src/Renamed.php');

		$this->assertSame(
			$this->walkWithFinder($this->directory, ['php']),
			(new DirectoryWalker($tmpDir))->walk($this->directory, ['php']),
		);
	}

	public function testReplacedDirectoryIsReadAgain(): void
	{
		$tmpDir = $this->createTree();
		$this->waitUntilTheTreeIsInThePast();
		(new DirectoryWalker($tmpDir))->walk($this->directory, ['php']);

		unlink($this->directory . '/src/Nested/Deep.php');
		rmdir($this->directory . '/src/Nested');
		mkdir($this->directory . '/src/Nested');
		file_put_contents($this->directory . '/src/Nested/Other.php', '<?php');

		$this->assertContains(
			$this->directory . '/src/Nested/Other.php',
			(new DirectoryWalker($tmpDir))->walk($this->directory, ['php']),
		);
	}

	private function createTree(): string
	{
		if (DIRECTORY_SEPARATOR !== '/') {
			$this->markTestSkipped('The listings are not used on Windows, and the tree needs symlinks.');
		}

		$tmpDir = $this->directory . '-tmp';
		mkdir($tmpDir);

		mkdir($this->directory . '/src');
		mkdir($this->directory . '/src/Nested');
		mkdir($this->directory . '/src/.hidden');
		mkdir($this->directory . '/src/CVS');
		mkdir($this->directory . '/bin');
		file_put_contents($this->directory . '/src/Foo.php', '<?php');
		file_put_contents($this->directory . '/src/Bar.php', '<?php');
		file_put_contents($this->directory . '/src/readme.md', 'x');
		file_put_contents($this->directory . '/src/.dotfile.php', '<?php');
		file_put_contents($this->directory . '/src/Nested/Deep.php', '<?php');
		file_put_contents($this->directory . '/src/.hidden/Hidden.php', '<?php');
		file_put_contents($this->directory . '/src/CVS/Versioned.php', '<?php');
		file_put_contents($this->directory . '/bin/tool.sh', '#!/bin/sh');
		file_put_contents($this->directory . '/bin/tool', '#!/bin/sh');
		file_put_contents($this->directory . '/bin/ends-with-dot.', 'x');

		$outside = $this->directory . '-outside';
		mkdir($outside);
		file_put_contents($outside . '/Linked.php', '<?php');
		symlink($outside, $this->directory . '/src/linked-directory');
		symlink($outside . '/Linked.php', $this->directory . '/src/LinkedFile.php');
		symlink($outside . '/Missing.php', $this->directory . '/src/Broken.php');

		return $tmpDir;
	}

	/**
	 * A listing is kept only for a directory that has not changed in the second it was read.
	 */
	private function waitUntilTheTreeIsInThePast(): void
	{
		clearstatcache();
		$latest = 0;
		foreach ([$this->directory, $this->directory . '/src', $this->directory . '/src/Nested', $this->directory . '/bin'] as $directory) {
			$stat = stat($directory);
			$this->assertIsArray($stat);
			$latest = max($latest, $stat['mtime'], $stat['ctime']);
		}

		while (time() <= $latest) {
			usleep(50_000);
		}
	}

	/**
	 * @param string[] $fileExtensions
	 * @return list<string>
	 */
	private function walkWithFinder(string $directory, array $fileExtensions): array
	{
		$files = [];
		foreach ((new Finder())->followLinks()->files()->name('*.{' . implode(',', $fileExtensions) . '}')->in($directory) as $fileInfo) {
			$files[] = $fileInfo->getPathname();
		}

		return $files;
	}

}
