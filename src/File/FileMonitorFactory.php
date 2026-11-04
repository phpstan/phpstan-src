<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function getenv;
use const PHP_OS_FAMILY;

/**
 * Picks the {@see FileMonitor} for this platform and hands it back initialized.
 *
 * Whether a native monitor can actually run is only known once it has opened
 * its kernel handle and armed every watch, so the fallback decision belongs
 * here rather than at the call site: a native monitor that cannot promise to
 * see every change is discarded whole, and {@see HashingFileMonitor} - correct
 * everywhere, just slower - answers instead.
 */
#[AutowiredService]
final class FileMonitorFactory
{

	/** Set to anything to force {@see HashingFileMonitor}. */
	private const DISABLE_ENV_VARIABLE = 'PHPSTAN_DISABLE_NATIVE_FILE_MONITOR';

	/**
	 * @param string[] $analysedPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param string[] $scanDirectories
	 */
	public function __construct(
		private HashingFileMonitor $hashingFileMonitor,
		#[AutowiredParameter]
		private array $analysedPaths,
		#[AutowiredParameter]
		private array $analysedPathsFromConfig,
		#[AutowiredParameter]
		private array $scanDirectories,
	)
	{
	}

	/**
	 * @param array<string> $filePaths extra files to monitor besides the analysed and scanned ones
	 */
	public function create(array $filePaths): FileMonitor
	{
		$native = $this->createNative();
		if ($native !== null) {
			try {
				$native->initialize($filePaths);

				return $native;
			} catch (FileMonitorNotSupportedException) {
				// fall through to hashing
			}
		}

		$this->hashingFileMonitor->initialize($filePaths);

		return $this->hashingFileMonitor;
	}

	private function createNative(): ?NativeFileMonitor
	{
		// escape hatch: the native monitors depend on FFI and on kernel
		// facilities a container or a hardened host can take away in ways they
		// cannot detect
		if (getenv(self::DISABLE_ENV_VARIABLE) !== false) {
			return null;
		}

		if (PHP_OS_FAMILY === 'Darwin') {
			return new FsEventsFileMonitor(
				$this->hashingFileMonitor,
				$this->analysedPaths,
				$this->analysedPathsFromConfig,
				$this->scanDirectories,
			);
		}

		if (PHP_OS_FAMILY === 'Linux') {
			return new InotifyFileMonitor(
				$this->hashingFileMonitor,
				$this->analysedPaths,
				$this->analysedPathsFromConfig,
				$this->scanDirectories,
			);
		}

		return null;
	}

}
