<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use FilesystemIterator;
use Override;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\Testing\PHPStanTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class FileCacheStorageTest extends PHPStanTestCase
{

	private string $directory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->directory = sys_get_temp_dir() . '/' . uniqid('phpstan-cache-', true);
	}

	#[Override]
	protected function tearDown(): void
	{
		parent::tearDown();

		if (!is_dir($this->directory)) {
			return;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST,
		);
		foreach ($files as $file) {
			if ($file->isDir()) {
				rmdir($file->getPathname());
				continue;
			}

			unlink($file->getPathname());
		}

		rmdir($this->directory);
	}

	public function testMissingEntry(): void
	{
		$storage = new FileCacheStorage($this->directory);

		$this->assertNull($storage->load('never-written', 'v1'));
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	public function testEntrySavedAfterAMissIsLoadedBack(): void
	{
		$storage = new FileCacheStorage($this->directory);

		// the miss comes first on purpose: load() stat()s the file to avoid a
		// failing include(), and PHP's stat cache must not answer the second
		// load() with what the first one saw
		$this->assertNull($storage->load('key', 'v1'));
		$storage->save('key', 'v1', ['data' => 1]);

		$this->assertSame(['data' => 1], $storage->load('key', 'v1'));
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	public function testEntryWithDifferentVariableKey(): void
	{
		$storage = new FileCacheStorage($this->directory);
		$storage->save('key', 'v1', ['data' => 1]);

		$this->assertNull($storage->load('key', 'v2'));
		$this->assertSame(['data' => 1], $storage->load('key', 'v1'));
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	public function testEntryWithObjectsRoundTrips(): void
	{
		$storage = new FileCacheStorage($this->directory);
		$shared = new CacheItem('inner', ['a' => 1]);
		$storage->save('key', 'v1', [$shared, $shared]);

		$loaded = $storage->load('key', 'v1');
		$this->assertIsArray($loaded);
		$this->assertInstanceOf(CacheItem::class, $loaded[0]);
		$this->assertSame(['a' => 1], $loaded[0]->getData());
		$this->assertSame($loaded[0], $loaded[1]);
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	public function testClearUnusedFilesRemovesEntriesOfTheFormerFormat(): void
	{
		$storage = new FileCacheStorage($this->directory);
		$storage->save('key', 'v1', ['data' => 1]);

		$formerEntryDirectory = $this->directory . '/ab/cd';
		mkdir($formerEntryDirectory, 0777, true);
		$formerEntry = $formerEntryDirectory . '/abcd.php';
		file_put_contents($formerEntry, "<?php declare(strict_types = 1);\n\n// key\nreturn null;");

		$storage->clearUnusedFiles();

		$this->assertFileDoesNotExist($formerEntry);
		$this->assertDirectoryDoesNotExist($this->directory . '/ab');
		$this->assertSame(['data' => 1], $storage->load('key', 'v1'));
	}

}
