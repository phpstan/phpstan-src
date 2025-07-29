<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\DependencyInjection\AutowiredService;
use function explode;
use function max;
use function min;
use const PHP_VERSION_ID;

#[AutowiredService(factory: '@PHPStan\Php\PhpVersionFactoryFactory::create')]
final class PhpVersionFactory
{

	public const MIN_PHP_VERSION = 70100;
	public const MAX_PHP_VERSION = 80599;
	public const MAX_PHP5_VERSION = 50699;
	public const MAX_PHP7_VERSION = 70499;

	public function __construct(
		private ?int $versionId,
		private ?string $composerPhpVersion,
	)
	{
	}

	public function create(): PhpVersion
	{
		$versionId = $this->versionId;
		if ($versionId !== null) {
			$source = PhpVersion::SOURCE_CONFIG;
		} elseif ($this->composerPhpVersion !== null) {
			$parts = explode('.', $this->composerPhpVersion);
			$tmp = (int) $parts[0] * 10000 + (int) ($parts[1] ?? 0) * 100 + (int) ($parts[2] ?? 0);
			$tmp = max($tmp, self::MIN_PHP_VERSION);
			$versionId = min($tmp, self::MAX_PHP_VERSION);
			$source = PhpVersion::SOURCE_COMPOSER_PLATFORM_PHP;
		} else {
			$versionId = PHP_VERSION_ID;
			$source = PhpVersion::SOURCE_RUNTIME;
		}

		return new PhpVersion($versionId, $source);
	}

}
