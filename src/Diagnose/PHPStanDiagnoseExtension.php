<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use Phar;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\NonAutowiredService;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\Process\PcovHelper;
use ReflectionClass;
use function array_count_values;
use function array_key_exists;
use function array_slice;
use function arsort;
use function class_exists;
use function count;
use function dirname;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_file;
use function is_readable;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use const PHP_VERSION_ID;

#[NonAutowiredService(name: 'phpstanDiagnoseExtension')]
final class PHPStanDiagnoseExtension
{

	/**
	 * @param int|array{min: int, max: int}|null $configPhpVersion
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string [] $allConfigFiles
	 */
	public function __construct(
		private PhpVersion $phpVersion,
		#[AutowiredParameter(ref: '%phpVersion%')]
		private int|array|null $configPhpVersion,
		private FileHelper $fileHelper,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		#[AutowiredParameter]
		private array $allConfigFiles,
		private ComposerPhpVersionFactory $composerPhpVersionFactory,
		#[AutowiredParameter(ref: '@simpleRelativePathHelper')]
		private RelativePathHelper $simpleRelativePathHelper,
	)
	{
	}

	/**
	 * @param list<string> $processedFiles
	 */
	public function print(Output $output, array $processedFiles): void
	{
		$phpRuntimeVersion = new PhpVersion(PHP_VERSION_ID);
		$output->writeLineFormatted(sprintf(
			'<info>PHP runtime version:</info> %s',
			$phpRuntimeVersion->getVersionString(),
		));

		if (PcovHelper::isLoaded()) {
			$output->writeLineFormatted(sprintf(
				'<info>pcov extension:</info> %s',
				$this->describePcovStatus(),
			));
		}

		if (
			$this->phpVersion->getSource() === PhpVersion::SOURCE_CONFIG
			&& is_array($this->configPhpVersion)
		) {
			$minVersion = new PhpVersion($this->configPhpVersion['min']);
			$maxVersion = new PhpVersion($this->configPhpVersion['max']);

			$output->writeLineFormatted(sprintf(
				'<info>PHP version for analysis:</info> %s-%s (from %s)',
				$minVersion->getVersionString(),
				$maxVersion->getVersionString(),
				$this->phpVersion->getSourceLabel(),
			));

		} else {
			$minComposerPhpVersion = $this->composerPhpVersionFactory->getMinVersion();
			$maxComposerPhpVersion = $this->composerPhpVersionFactory->getMaxVersion();
			if ($minComposerPhpVersion !== null && $maxComposerPhpVersion !== null) {
				if ($minComposerPhpVersion->getVersionId() !== $maxComposerPhpVersion->getVersionId()) {
					$output->writeLineFormatted(sprintf(
						'<info>PHP composer.json required version:</info> %s-%s',
						$minComposerPhpVersion->getVersionString(),
						$maxComposerPhpVersion->getVersionString(),
					));
				} else {
					$output->writeLineFormatted(sprintf(
						'<info>PHP composer.json required version:</info> %s',
						$minComposerPhpVersion->getVersionString(),
					));
				}
			}

			$output->writeLineFormatted(sprintf(
				'<info>PHP version for analysis:</info> %s (from %s)',
				$this->phpVersion->getVersionString(),
				$this->phpVersion->getSourceLabel(),
			));
		}
		$output->writeLineFormatted('');

		$output->writeLineFormatted(sprintf(
			'<info>PHPStan version:</info> %s',
			ComposerHelper::getPhpStanVersion(),
		));
		$output->writeLineFormatted('<info>PHPStan running from:</info>');
		$pharRunning = Phar::running(false);
		if ($pharRunning !== '') {
			$output->writeLineFormatted(dirname($pharRunning));
		} else {
			if (isset($_SERVER['argv'][0]) && is_file($_SERVER['argv'][0])) {
				$output->writeLineFormatted($_SERVER['argv'][0]);
			} else {
				$output->writeLineFormatted('Unknown');
			}
		}
		$output->writeLineFormatted('');

		$configFilesFromExtensionInstaller = [];
		if (class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
			$output->writeLineFormatted('<info>Extension installer:</info>');
			if (count(GeneratedConfig::EXTENSIONS) === 0) {
				$output->writeLineFormatted('No extensions installed');
			}

			$generatedConfigReflection = new ReflectionClass('PHPStan\ExtensionInstaller\GeneratedConfig');
			$generatedConfigDirectory = dirname($generatedConfigReflection->getFileName());
			foreach (GeneratedConfig::EXTENSIONS as $name => $extensionConfig) {
				$output->writeLineFormatted(sprintf('%s: %s', $name, $extensionConfig['version'] ?? 'Unknown version'));
				foreach ($extensionConfig['extra']['includes'] ?? [] as $includedFile) {
					$includedFilePath = null;
					if (isset($extensionConfig['relative_install_path'])) {
						$includedFilePath = sprintf('%s/%s/%s', $generatedConfigDirectory, $extensionConfig['relative_install_path'], $includedFile);
						if (!is_file($includedFilePath) || !is_readable($includedFilePath)) {
							$includedFilePath = null;
						}
					}

					if ($includedFilePath === null) {
						$includedFilePath = sprintf('%s/%s', $extensionConfig['install_path'], $includedFile);
					}

					$configFilesFromExtensionInstaller[] = $this->fileHelper->normalizePath($includedFilePath, '/');
				}
			}
		} else {
			$output->writeLineFormatted('<info>Extension installer:</info> Not installed');
		}
		$output->writeLineFormatted('');

		$thirdPartyIncludedConfigs = [];
		foreach ($this->allConfigFiles as $configFile) {
			$configFile = $this->fileHelper->normalizePath($configFile, '/');
			if (in_array($configFile, $configFilesFromExtensionInstaller, true)) {
				continue;
			}
			foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
				$composerConfig = ComposerHelper::getComposerConfig($composerAutoloaderProjectPath);
				if ($composerConfig === null) {
					continue;
				}
				$vendorDir = $this->fileHelper->normalizePath(ComposerHelper::getVendorDirFromComposerConfig($composerAutoloaderProjectPath, $composerConfig), '/');
				if (!str_starts_with($configFile, $vendorDir)) {
					continue;
				}

				$installedPath = $vendorDir . '/composer/installed.php';
				if (!is_file($installedPath)) {
					continue;
				}

				$installed = require $installedPath;

				$trimmed = substr($configFile, strlen($vendorDir) + 1);
				$parts = explode('/', $trimmed);
				$package = implode('/', array_slice($parts, 0, 2));
				$configPath = implode('/', array_slice($parts, 2));
				if (!array_key_exists($package, $installed['versions'])) {
					continue;
				}

				$packageVersion = $installed['versions'][$package]['pretty_version'] ?? null;
				if ($packageVersion === null) {
					continue;
				}

				$thirdPartyIncludedConfigs[] = [$package, $packageVersion, $configPath];
			}
		}

		if (count($thirdPartyIncludedConfigs) > 0) {
			$output->writeLineFormatted('<info>Included configs from Composer packages:</info>');
			foreach ($thirdPartyIncludedConfigs as [$package, $packageVersion, $configPath]) {
				$output->writeLineFormatted(sprintf('%s (%s): %s', $package, $configPath, $packageVersion));
			}
			$output->writeLineFormatted('');
		}

		$composerAutoloaderProjectPathsCount = count($this->composerAutoloaderProjectPaths);
		$output->writeLineFormatted(sprintf(
			'<info>Discovered Composer project %s:</info>',
			$composerAutoloaderProjectPathsCount === 1 ? 'root' : 'roots',
		));
		if ($composerAutoloaderProjectPathsCount === 0) {
			$output->writeLineFormatted('None');
		}
		foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
			$output->writeLineFormatted($composerAutoloaderProjectPath);
		}
		$output->writeLineFormatted('');

		$topFiles = $this->getTopMostAnalysedFiles($processedFiles, 5);
		if (count($topFiles) <= 0) {
			return;
		}

		$output->writeLineFormatted('<info>Most often analysed files:</info>');
		foreach ($topFiles as $file => $count) {
			$output->writeLineFormatted(sprintf(
				'  %s: %d times',
				$this->simpleRelativePathHelper->getRelativePath($file),
				$count,
			));
		}
		$output->writeLineFormatted('');
	}

	private function describePcovStatus(): string
	{
		$version = PcovHelper::getVersion() ?? 'unknown version';

		if (PcovHelper::isAllowed()) {
			return sprintf(
				'%s, %s - kept everywhere because %s=1',
				$version,
				PcovHelper::isActive() ? 'active' : 'not active (pcov.enabled=0)',
				PcovHelper::ALLOW_ENV_VARIABLE,
			);
		}

		if (!PcovHelper::isActive()) {
			return sprintf('%s, not active in this process (pcov.enabled=0), disabled in worker processes', $version);
		}

		return sprintf('%s, active - it slows down every function call, disabled in worker processes', $version);
	}

	/**
	 * @param list<string> $processedFiles
	 * @return array<string, int<2, max>>
	 */
	private function getTopMostAnalysedFiles(array $processedFiles, int $limit): array
	{
		if ($processedFiles === []) {
			return [];
		}

		$counts = array_count_values($processedFiles);
		arsort($counts);

		$result = [];
		foreach (array_slice($counts, 0, $limit, true) as $file => $count) {
			if ($count <= 1) {
				continue;
			}
			$result[$file] = $count;
		}

		return $result;
	}

}
