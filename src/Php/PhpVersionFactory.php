<?php declare(strict_types = 1);

namespace PHPStan\Php;

use function explode;
use function max;
use function min;
use const PHP_VERSION_ID;

class PhpVersionFactory
{

	private ?int $versionId;

	private ?string $composerPhpVersion;

	public function __construct(
		?int $versionId,
		?string $composerPhpVersion
	)
	{
		$this->versionId = $versionId;
		$this->composerPhpVersion = $composerPhpVersion;
	}

	public function create(): PhpVersion
	{
		$versionId = $this->versionId;
		if ($versionId === null && $this->composerPhpVersion !== null) {
			$parts = explode('.', $this->composerPhpVersion);
			$tmp = (int) $parts[0] * 10000 + (int) ($parts[1] ?? 0) * 100 + (int) ($parts[2] ?? 0);
			$tmp = max($tmp, 70100);
			$versionId = min($tmp, 80199);
		}

		if ($versionId === null) {
			$versionId = PHP_VERSION_ID;
		}

		return new PhpVersion($versionId);
	}

}
