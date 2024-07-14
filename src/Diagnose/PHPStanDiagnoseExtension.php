<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use Phar;
use PHPStan\Command\Output;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use function class_exists;
use function count;
use function dirname;
use function is_file;
use function sprintf;
use const PHP_VERSION_ID;

class PHPStanDiagnoseExtension implements DiagnoseExtension
{

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private PhpVersion $phpVersion,
		private array $composerAutoloaderProjectPaths,
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

		$output->writeLineFormatted('<info>Discovered Composer project roots:</info>');
		if (count($this->composerAutoloaderProjectPaths) === 0) {
			$output->writeLineFormatted('None');
		}
		foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
			$output->writeLineFormatted($composerAutoloaderProjectPath);
		}
		$output->writeLineFormatted('');
	}

}
