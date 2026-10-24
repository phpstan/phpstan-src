<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\Utils\FileSystem;
use Override;
use PHPStan\File\FileHelper;
use PHPUnit\Framework\TestCase;
use function sys_get_temp_dir;
use function uniqid;

final class ConfiguratorContainerKeyTest extends TestCase
{

	private string $tempDirectory;

	#[Override]
	protected function setUp(): void
	{
		$this->tempDirectory = sys_get_temp_dir() . '/' . uniqid('phpstan-configurator-key-', true);
	}

	#[Override]
	protected function tearDown(): void
	{
		FileSystem::delete($this->tempDirectory);
	}

	public function testTheRestartIniDoesNotChangeTheContainer(): void
	{
		$first = $this->loadContainer([
			'PHPRC' => '/tmp/phpstan-restart-ini-first',
			'PHP_INI_SCAN_DIR' => '',
		]);
		$second = $this->loadContainer([
			'PHPRC' => '/tmp/phpstan-restart-ini-second',
		]);

		$this->assertSame($first, $second);
	}

	public function testOtherEnvironmentVariablesStillChangeTheContainer(): void
	{
		$first = $this->loadContainer(['FOO' => 'first']);
		$second = $this->loadContainer(['FOO' => 'second']);

		$this->assertNotSame($first, $second);
	}

	/**
	 * @param array<string, string> $env
	 */
	private function loadContainer(array $env): string
	{
		$configurator = new Configurator(new LoaderFactory(new FileHelper(__DIR__), __DIR__, __DIR__, null, []), false);
		$configurator->setDebugMode(true);
		$configurator->setTempDirectory($this->tempDirectory);
		$configurator->addStaticParameters(['env' => $env]);

		return $configurator->loadContainer();
	}

}
