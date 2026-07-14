<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Diagnose\DiagnoseExtension;
use function getenv;
use function php_uname;
use function phpversion;
use function sprintf;
use const PHP_DEBUG;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_OS_FAMILY;
use const PHP_ZTS;

#[AutowiredService]
final class TurboDiagnoseExtension implements DiagnoseExtension
{

	public function print(Output $output): void
	{
		$workerBinary = TurboExtensionSelector::findExtensionForWorkers();

		$output->writeLineFormatted(sprintf(
			'<info>Turbo extension:</info> %s',
			$this->describeStatus($workerBinary),
		));

		$isMusl = TurboExtensionSelector::isMusl();
		$platform = TurboExtensionSelector::resolvePlatformDirectory(PHP_OS_FAMILY, php_uname('m'), $isMusl);
		$output->writeLineFormatted(sprintf(
			'<info>Turbo platform:</info> %s (os: %s, machine: %s, libc: %s, php: %d.%d, zts: %s, debug: %s)',
			$platform ?? 'unsupported',
			PHP_OS_FAMILY,
			php_uname('m'),
			$isMusl ? 'musl' : 'gnu',
			PHP_MAJOR_VERSION,
			PHP_MINOR_VERSION,
			(bool) PHP_ZTS ? 'yes' : 'no',
			(bool) PHP_DEBUG ? 'yes' : 'no',
		));

		if (TurboExtensionEnabler::isLoaded()) {
			$workerBinaryLine = 'loaded via php.ini, workers inherit it';
		} else {
			$workerBinaryLine = $workerBinary ?? 'none found';
		}
		$output->writeLineFormatted(sprintf(
			'<info>Turbo worker binary:</info> %s',
			$workerBinaryLine,
		));
		$output->writeLineFormatted('');
	}

	private function describeStatus(?string $workerBinary): string
	{
		if (!TurboExtensionEnabler::isLoaded()) {
			if ($workerBinary !== null) {
				return 'enabled in worker processes (the main process runs without it)';
			}

			return 'not loaded';
		}
		if (getenv('PHPSTAN_TURBO') === '0') {
			return 'disabled via PHPSTAN_TURBO=0';
		}

		$loadedVersion = phpversion('phpstan_turbo');
		if ($loadedVersion !== TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION) {
			return sprintf(
				'inactive (extension version %s, expected %s)',
				$loadedVersion === false ? 'unknown' : $loadedVersion,
				TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION,
			);
		}

		return sprintf('enabled (version %s)', TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION);
	}

}
