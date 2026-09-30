<?php declare(strict_types = 1);

namespace PHPStan\File;

use Override;
use PHPUnit\Framework\TestCase;
use function clearstatcache;
use function file_put_contents;
use function max;
use function stat;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class FileStatSignatureReaderTest extends TestCase
{

	private string $file;

	#[Override]
	protected function setUp(): void
	{
		$this->file = sys_get_temp_dir() . '/' . uniqid('phpstan-stat-', true) . '.php';
		file_put_contents($this->file, '<?php');
	}

	#[Override]
	protected function tearDown(): void
	{
		@unlink($this->file);
	}

	public function testSignatureStaysWhileTheFileIsUnchanged(): void
	{
		$reader = new FileStatSignatureReader($this->getLastChange() + 1, true);

		$signature = $reader->get($this->file);

		$this->assertNotNull($signature);
		$this->assertSame($signature, $reader->get($this->file));
	}

	public function testSignatureChangesWithTheFile(): void
	{
		$before = (new FileStatSignatureReader($this->getLastChange() + 1, true))->get($this->file);
		file_put_contents($this->file, '<?php echo 1;');
		clearstatcache();
		$after = (new FileStatSignatureReader($this->getLastChange() + 1, true))->get($this->file);

		$this->assertNotNull($before);
		$this->assertNotNull($after);
		$this->assertNotSame($before, $after);
	}

	public function testNoSignatureForFileModifiedInTheSecondTheReadingBegan(): void
	{
		$this->assertNull((new FileStatSignatureReader($this->getLastChange(), true))->get($this->file));
	}

	public function testNoSignatureWhenNotTrusted(): void
	{
		$this->assertNull((new FileStatSignatureReader($this->getLastChange() + 1, false))->get($this->file));
	}

	public function testNoSignatureForMissingFileOrStreamWrapper(): void
	{
		$reader = new FileStatSignatureReader($this->getLastChange() + 1, true);

		$this->assertNull($reader->get($this->file . '.missing'));
		$this->assertNull($reader->get('file://' . $this->file));
	}

	private function getLastChange(): int
	{
		$stat = stat($this->file);
		$this->assertIsArray($stat);

		return max($stat['mtime'], $stat['ctime']);
	}

}
