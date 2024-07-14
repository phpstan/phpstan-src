<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use PHPStan\Command\Output;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use function class_exists;
use function count;
use function sprintf;
use const PHP_VERSION_ID;

class PHPStanDiagnoseExtension implements DiagnoseExtension
{

	public function __construct(private PhpVersion $phpVersion)
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
		$output->writeLineFormatted(sprintf(
			'<info>PHPStan version:</info> %s',
			ComposerHelper::getPhpStanVersion(),
		));
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
	}

}
