<?php declare(strict_types = 1);

namespace PHPStan\Cache;

use Override;
use PHPStan\Internal\DirectoryCreatorException;
use PHPUnit\Framework\TestCase;
use function exec;
use function escapeshellarg;
use function file_put_contents;
use function is_file;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

class FileCacheStorageTest extends TestCase
{

	private string $directory;

	#[Override]
	protected function setUp(): void
	{
		$this->directory = sys_get_temp_dir() . '/phpstan-file-cache-storage-test-' . uniqid();
		mkdir($this->directory, 0777, true);
	}

	#[Override]
	protected function tearDown(): void
	{
		exec('rm -rf ' . escapeshellarg($this->directory));
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	public function testSaveAndLoadRoundTrip(): void
	{
		$storage = new FileCacheStorage($this->directory);
		$storage->save('some-key', 'variable-key', ['data' => [1, 2, 3]]);

		$this->assertSame(['data' => [1, 2, 3]], $storage->load('some-key', 'variable-key'));
		$this->assertNull($storage->load('some-key', 'different-variable-key'));
		$this->assertNull($storage->load('unknown-key', 'variable-key'));
	}

	/**
	 * @throws DirectoryCreatorException
	 */
	public function testClearUnusedFilesKeepsCurrentFormatEntries(): void
	{
		$storage = new FileCacheStorage($this->directory);
		$storage->save('some-key', 'variable-key', 'cached-value');

		// no cache-cleared marker exists yet - cleanup must not treat
		// current-format entries as legacy garbage
		$storage->clearUnusedFiles();

		$this->assertSame('cached-value', $storage->load('some-key', 'variable-key'));
	}

	public function testClearUnusedFilesRemovesLegacyFormatEntries(): void
	{
		$storage = new FileCacheStorage($this->directory);
		$legacyFile = $this->directory . '/ab/cd/legacy.php';
		mkdir($this->directory . '/ab/cd', 0777, true);
		file_put_contents($legacyFile, "<?php declare(strict_types = 1);\n\n// legacy-key\nreturn 'legacy';");

		$storage->clearUnusedFiles();

		$this->assertFalse(is_file($legacyFile));
	}

}
