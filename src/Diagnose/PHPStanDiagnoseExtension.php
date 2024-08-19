<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use Phar;
use PHPStan\Command\Output;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\File\FileHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use function array_key_exists;
use function array_slice;
use function class_exists;
use function count;
use function dirname;
use function explode;
use function implode;
use function is_file;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use const PHP_VERSION_ID;

final class PHPStanDiagnoseExtension implements DiagnoseExtension
{

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string [] $allConfigFiles
	 */
	public function __construct(
		private PhpVersion $phpVersion,
		private FileHelper $fileHelper,
		private array $composerAutoloaderProjectPaths,
		private array $allConfigFiles,
	)
	{
	}

	public function print(Output $output): void
	{
		$phpRuntimeVersion = new PhpVersion(PHP_VERSION_ID);
		$output->writeLineFormatted(sprintf(
			'<info>PHP runtime version:</info> %s',
			$phpRuntimeVersion->getVersionString(),
		));
		$output->writeLineFormatted(sprintf(
			'<info>PHP version for analysis:</info> %s (from %s)',
			$this->phpVersion->getVersionString(),
			$this->phpVersion->getSourceLabel(),
		));
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

		if (class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
			$output->writeLineFormatted('<info>Extension installer:</info>');
			if (count(GeneratedConfig::EXTENSIONS) === 0) {
				$output->writeLineFormatted('No extensions installed');
			}
			foreach (GeneratedConfig::EXTENSIONS as $name => $extensionConfig) {
				$output->writeLineFormatted(sprintf('%s: %s', $name, $extensionConfig['version'] ?? 'Unknown version'));
			}
		} else {
			$output->writeLineFormatted('<info>Extension installer:</info> Not installed');
		}
		$output->writeLineFormatted('');

		$thirdPartyIncludedConfigs = [];
		foreach ($this->allConfigFiles as $configFile) {
			$configFile = $this->fileHelper->normalizePath($configFile, '/');
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
	}

}
