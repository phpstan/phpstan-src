<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use FilesystemIterator;
use Override;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\Testing\PHPStanTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function is_dir;
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

}
