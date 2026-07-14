<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Phar;
use function count;
use function dirname;
use function getenv;
use function glob;
use function is_file;
use function php_uname;
use function sprintf;
use const PHP_DEBUG;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_OS_FAMILY;
use const PHP_ZTS;

/**
 * Locates the distributed turbo extension binary matching the current runtime
 * so worker processes can load it via `-d extension=`.
 *
 * The binaries are committed to the phpstan/phpstan repository next to
 * phpstan.phar (turbo-ext/<platform>/phpstan_turbo-<minor>.so) by the
 * phar.yml commit job, so they only exist for phar-based installations —
 * a source checkout loads its locally built extension through php.ini
 * instead. Only NTS non-debug builds are shipped. Workers run the regular
 * entrypoint, so TurboExtensionEnabler still gates activation on the
 * expected extension version.
 */
final class TurboExtensionSelector
{

	public static function findExtensionForWorkers(): ?string
	{
		if (TurboExtensionEnabler::isLoaded()) {
			// loaded through php.ini — workers inherit the ini file
			return null;
		}
		if (getenv('PHPSTAN_TURBO') === '0') {
			return null;
		}
		if ((bool) PHP_ZTS || (bool) PHP_DEBUG) {
			return null;
		}

		$pharPath = Phar::running(false);
		if ($pharPath === '') {
			return null;
		}

		$platform = self::resolvePlatformDirectory(PHP_OS_FAMILY, php_uname('m'), self::isMusl());
		if ($platform === null) {
			return null;
		}

		$file = sprintf('%s/turbo-ext/%s/phpstan_turbo-%d.%d.so', dirname($pharPath), $platform, PHP_MAJOR_VERSION, PHP_MINOR_VERSION);
		if (!is_file($file)) {
			return null;
		}

		return $file;
	}

	public static function resolvePlatformDirectory(string $osFamily, string $machine, bool $isMusl): ?string
	{
		if ($osFamily === 'Darwin') {
			// one universal binary covers x86_64 and arm64
			return 'macos';
		}
		if ($osFamily !== 'Linux') {
			return null;
		}

		$architecture = $machine === 'aarch64' ? 'arm64' : $machine;
		if ($architecture !== 'x86_64' && $architecture !== 'arm64') {
			return null;
		}

		return sprintf('linux-%s-%s', $isMusl ? 'musl' : 'gnu', $architecture);
	}

	/**
	 * libc has no PHP constant; this is the same filesystem heuristic
	 * datadog-setup.php uses.
	 */
	public static function isMusl(): bool
	{
		if (is_file('/etc/alpine-release')) {
			return true;
		}

		$muslLoaders = glob('/lib/ld-musl-*');

		return $muslLoaders !== false && count($muslLoaders) > 0;
	}

}
