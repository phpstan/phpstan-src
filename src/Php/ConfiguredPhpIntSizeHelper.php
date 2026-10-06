<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\ComposerHelper;
use function count;
use function end;
use function is_string;

/**
 * Tells whether the analysed code is guaranteed to run on a 64bit platform.
 *
 * The source is either the NEON config phpIntSize value, or a `php-64bit` requirement
 * in the project's composer.json. Composer only provides the `php-64bit` virtual package
 * when PHP_INT_SIZE === 8, so requiring it makes the assumption enforceable at install time.
 *
 * Reading composer.json turns the assumption on for projects that never asked for it,
 * so it only happens under the composerPhp64Bit feature toggle. Setting phpIntSize is
 * explicit and always wins.
 */
#[AutowiredService]
final class ConfiguredPhpIntSizeHelper
{

	/** The only int size that can be assumed. 32bit semantics are not modelled anywhere else. */
	private const SUPPORTED_INT_SIZE = 8;

	/** @var self::SUPPORTED_INT_SIZE|null */
	private ?int $intSize = null;

	private bool $initialized = false;

	/**
	 * @param self::SUPPORTED_INT_SIZE|null $configPhpIntSize
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		#[AutowiredParameter(ref: '%phpIntSize%')]
		private ?int $configPhpIntSize,
		#[AutowiredParameter(ref: '%featureToggles.composerPhp64Bit%')]
		private bool $composerPhp64Bit,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
	)
	{
	}

	/**
	 * Size of an integer in bytes on the analysed platform,
	 * or null when both 32bit and 64bit have to be taken into account.
	 *
	 * @return self::SUPPORTED_INT_SIZE|null
	 */
	public function getIntSize(): ?int
	{
		if (!$this->initialized) {
			$this->initialized = true;
			$this->intSize = $this->configPhpIntSize ?? ($this->composerRequiresPhp64Bit() ? self::SUPPORTED_INT_SIZE : null);
		}

		return $this->intSize;
	}

	private function composerRequiresPhp64Bit(): bool
	{
		if (!$this->composerPhp64Bit) {
			return false;
		}

		if (count($this->composerAutoloaderProjectPaths) === 0) {
			return false;
		}

		$composer = ComposerHelper::getComposerConfig(end($this->composerAutoloaderProjectPaths));
		if ($composer === null) {
			return false;
		}

		return is_string($composer['require']['php-64bit'] ?? null);
	}

}
