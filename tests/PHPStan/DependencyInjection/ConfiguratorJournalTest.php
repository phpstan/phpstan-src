<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\Utils\FileSystem;
use Override;
use PHPStan\File\FileHelper;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function is_file;
use function sprintf;
use function sys_get_temp_dir;
use function time;
use function touch;
use function uniqid;

final class ConfiguratorJournalTest extends TestCase
{

	private string $tempDirectory;

	#[Override]
	protected function setUp(): void
	{
		$this->tempDirectory = sys_get_temp_dir() . '/' . uniqid('phpstan-configurator-journal-', true);
	}

	#[Override]
	protected function tearDown(): void
	{
		FileSystem::delete($this->tempDirectory);
	}

	public function testKeepsAContainerThatAnotherProcessHasNotJournaledYet(): void
	{
		$configurator = new Configurator(new LoaderFactory(new FileHelper(__DIR__), __DIR__, __DIR__, null, []), true);
		$configurator->setTempDirectory($this->tempDirectory);
		$directory = $configurator->getContainerCacheDirectory();
		FileSystem::createDir($directory);

		$now = time();
		$eightDaysAgo = $now - 3600 * 24 * 8;
		file_put_contents($directory . '/container.journal', sprintf("Container_used:%d\nContainer_expired:%d\n", $now, $eightDaysAgo));
		foreach (['Container_used', 'Container_expired', 'Container_current', 'Container_unjournaled'] as $className) {
			file_put_contents($directory . '/' . $className . '.php', '<?php');
			touch($directory . '/' . $className . '.php', $eightDaysAgo);
		}
		// written by another process that has not journaled it yet
		file_put_contents($directory . '/Container_fresh.php', '<?php');

		(fn () => $this->journal('Container_current'))->call($configurator);

		$this->assertFileExists($directory . '/Container_used.php');
		$this->assertFileExists($directory . '/Container_current.php');
		$this->assertFileExists($directory . '/Container_fresh.php');
		$this->assertFalse(is_file($directory . '/Container_expired.php'));
		$this->assertFalse(is_file($directory . '/Container_unjournaled.php'));
	}

}
