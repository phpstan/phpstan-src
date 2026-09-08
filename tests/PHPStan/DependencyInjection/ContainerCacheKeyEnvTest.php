<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Override;
use PHPStan\File\FileHelper;
use PHPUnit\Framework\TestCase;
use function count;
use function getenv;
use function glob;
use function is_dir;
use function md5;
use function putenv;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use const DIRECTORY_SEPARATOR;

final class ContainerCacheKeyEnvTest extends TestCase
{

	private const ENV_VAR = 'PHPSTAN_TEST_W1_CACHE_KEY';

	private string $tmpDir;

	private string|false $envBackup;

	#[Override]
	protected function setUp(): void
	{
		$this->envBackup = getenv(self::ENV_VAR);
		$this->tmpDir = sys_get_temp_dir() . '/phpstan-container-cache-key-' . md5(uniqid());
		$this->removeDirectory($this->tmpDir);
	}

	#[Override]
	protected function tearDown(): void
	{
		if ($this->envBackup === false) {
			putenv(self::ENV_VAR);
		} else {
			putenv(sprintf('%s=%s', self::ENV_VAR, $this->envBackup));
		}

		$this->removeDirectory($this->tmpDir);
	}

	public function testEnvironmentIsDroppedFromCacheKeyWhenNoConfigReferencesIt(): void
	{
		// No loaded config uses %env.*%, so an unrelated env change must NOT recompile the container.
		putenv(sprintf('%s=first', self::ENV_VAR));
		$this->buildContainer(__DIR__ . '/containerCacheKey-noenv.neon');

		putenv(sprintf('%s=second', self::ENV_VAR));
		$this->buildContainer(__DIR__ . '/containerCacheKey-noenv.neon');

		$this->assertSame(
			1,
			$this->countCompiledContainers(),
			'Changing an unrelated environment variable should reuse the cached container.',
		);
	}

	public function testEnvironmentStaysInCacheKeyWhenAConfigReferencesIt(): void
	{
		// A loaded config uses %env.*%, so the environment is relevant and a change must recompile.
		putenv(sprintf('%s=first', self::ENV_VAR));
		$this->buildContainer(__DIR__ . '/containerCacheKey-withenv.neon');

		putenv(sprintf('%s=second', self::ENV_VAR));
		$this->buildContainer(__DIR__ . '/containerCacheKey-withenv.neon');

		$this->assertSame(
			2,
			$this->countCompiledContainers(),
			'When a config references %env.*%, changing it must recompile the container.',
		);
	}

	public function testDashedEnvVariableReferenceStaysInCacheKey(): void
	{
		// %env.MY-VAR% is a valid Nette reference (parameter-name grammar %([\w.-]*)%). The env-name
		// extraction must keep dash/dot/digit-start names too - otherwise changing such a referenced
		// var would reuse a stale container, the regression class W1 fixes.
		$envName = 'PHPSTAN_TEST_W1-DASH';
		$backup = getenv($envName);
		try {
			putenv(sprintf('%s=first', $envName));
			$this->buildContainer(__DIR__ . '/containerCacheKey-withenv-dashed.neon');

			putenv(sprintf('%s=second', $envName));
			$this->buildContainer(__DIR__ . '/containerCacheKey-withenv-dashed.neon');

			$this->assertSame(
				2,
				$this->countCompiledContainers(),
				'A %env.* reference whose name contains a dash must keep that var in the cache key.',
			);
		} finally {
			if ($backup === false) {
				putenv($envName);
			} else {
				putenv(sprintf('%s=%s', $envName, $backup));
			}
		}
	}

	public function testEnvironmentReferencedInServiceFactoryStaysInCacheKey(): void
	{
		// %env.* referenced inside a service factory (a Neon entity), not a plain parameter value.
		// Enumerating references through Nette's expander reaches this position; dropping it would
		// reuse a stale container when the variable changes.
		putenv(sprintf('%s=first', self::ENV_VAR));
		$this->buildContainer(__DIR__ . '/containerCacheKey-withenv-factory.neon');

		putenv(sprintf('%s=second', self::ENV_VAR));
		$this->buildContainer(__DIR__ . '/containerCacheKey-withenv-factory.neon');

		$this->assertSame(
			2,
			$this->countCompiledContainers(),
			'A %env.* reference inside a service definition must keep that var in the cache key.',
		);
	}

	public function testEnvironmentReferencedOnlyInACommentIsDroppedFromCacheKey(): void
	{
		// %env.* appearing only in a comment is not a real reference. Nette's parser ignores comments,
		// so the variable is dropped from the key and an unrelated change does not recompile - a raw
		// text scan would instead treat the comment as a reference and recompile spuriously.
		putenv(sprintf('%s=first', self::ENV_VAR));
		$this->buildContainer(__DIR__ . '/containerCacheKey-comment-env.neon');

		putenv(sprintf('%s=second', self::ENV_VAR));
		$this->buildContainer(__DIR__ . '/containerCacheKey-comment-env.neon');

		$this->assertSame(
			1,
			$this->countCompiledContainers(),
			'A %env.* mentioned only in a comment must not keep that var in the cache key.',
		);
	}

	private function buildContainer(string $additionalConfigFile): void
	{
		$rootDir = __DIR__ . '/../../..';
		$fileHelper = new FileHelper($rootDir);
		$rootDir = $fileHelper->normalizePath($rootDir, '/');
		$containerFactory = new ContainerFactory($rootDir);
		$containerFactory->create(
			$this->tmpDir,
			[
				$containerFactory->getConfigDirectory() . '/config.level8.neon',
				$additionalConfigFile,
			],
			[],
		);
	}

	private function countCompiledContainers(): int
	{
		$containers = glob($this->tmpDir . '/cache/nette.configurator/Container_*.php');

		return $containers === false ? 0 : count($containers);
	}

	private function removeDirectory(string $directory): void
	{
		if (!is_dir($directory)) {
			return;
		}

		$entries = glob($directory . DIRECTORY_SEPARATOR . '*');
		foreach ($entries === false ? [] : $entries as $entry) {
			if (is_dir($entry)) {
				$this->removeDirectory($entry);
			} else {
				@unlink($entry);
			}
		}

		@rmdir($directory);
	}

}
