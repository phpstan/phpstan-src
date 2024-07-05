<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use PHPStan\Command\Output;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use function sprintf;
use const PHP_VERSION_ID;

class PHPStanDiagnoseExtension implements DiagnoseExtension
{

	public function print(Output $errorOutput): void
	{
		$phpRuntimeVersion = new PhpVersion(PHP_VERSION_ID);
		$errorOutput->writeLineFormatted(sprintf(
			'<info>PHP runtime version:</info> %s',
			$phpRuntimeVersion->getVersionString(),
		));
		$errorOutput->writeLineFormatted(sprintf(
			'<info>PHPStan version:</info> %s',
			ComposerHelper::getPhpStanVersion(),
		));
		$errorOutput->writeLineFormatted('');
	}

}
