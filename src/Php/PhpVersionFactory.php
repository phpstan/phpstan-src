<?php declare(strict_types = 1);

namespace PHPStan\Php;

use function explode;
use function max;
use function min;
use const PHP_VERSION_ID;

final class PhpVersionFactory
{

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
			$tmp = max($tmp, 70100);
			$versionId = min($tmp, 80399);
			$source = PhpVersion::SOURCE_COMPOSER_PLATFORM_PHP;
		} else {
			$versionId = PHP_VERSION_ID;
			$source = PhpVersion::SOURCE_RUNTIME;
		}

		return new PhpVersion($versionId, $source);
	}

}
