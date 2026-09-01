<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Phar;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Diagnose\DiagnoseExtension;
use PHPStan\Php\PhpVersion;
use function php_uname;
use function phpversion;
use function sprintf;
use const PHP_DEBUG;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_OS_FAMILY;
use const PHP_VERSION_ID;
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
			$restartPath = TurboProcessRestarter::getRestartExtensionPath();
			if ($restartPath !== null) {
				$workerBinaryLine = sprintf('%s (loaded via process restart)', $restartPath);
			} else {
				$workerBinaryLine = 'loaded via php.ini, workers inherit it';
			}
		} elseif ($workerBinary !== null) {
			$workerBinaryLine = $workerBinary;
		} elseif (PHP_VERSION_ID < TurboExtensionSelector::MINIMUM_PHP_VERSION_ID) {
			$workerBinaryLine = sprintf(
				'none built for PHP < %s',
				(new PhpVersion(TurboExtensionSelector::MINIMUM_PHP_VERSION_ID))->getVersionString(),
			);
		} else {
			$workerBinaryLine = 'none found';
		}
		$output->writeLineFormatted(sprintf(
			'<info>Turbo worker binary:</info> %s',
			$workerBinaryLine,
		));
		$output->writeLineFormatted(sprintf(
			'<info>Turbo trusted types:</info> %s',
			$this->describeTrustedTypes(),
		));
		$output->writeLineFormatted('');
	}

	private function describeTrustedTypes(): string
	{
		if (TurboExtensionEnabler::isTrustingOwnTypes()) {
			return 'on (PHPStan\'s own argument and return type checks are dropped; --debug keeps them)';
		}
		if (!TurboExtensionEnabler::isActive()) {
			return 'off (extension inactive)';
		}
		if (Phar::running(false) === '') {
			return 'off (not running from a phar)';
		}

		return 'off (--debug, or OPcache is not active)';
	}

	private function describeStatus(?string $workerBinary): string
	{
		if (!TurboExtensionEnabler::isLoaded()) {
			if ($workerBinary !== null) {
				return 'enabled in worker processes (the main process runs without it)';
			}

			return 'not loaded';
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
