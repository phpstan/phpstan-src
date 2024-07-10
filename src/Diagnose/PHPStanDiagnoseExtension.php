<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use PHPStan\Command\Output;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
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
	}

}
