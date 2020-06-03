<?php declare(strict_types = 1);

namespace PHPStan\Php;

use const PHP_VERSION_ID;

class PhpVersionFactory
{

	private ?int $versionId;

	public function __construct(?int $versionId)
	{
		$this->versionId = $versionId;
	}

	public function create(): PhpVersion
	{
		$versionId = $this->versionId;
		if ($versionId === null) {
			$versionId = PHP_VERSION_ID;
		}

		return new PhpVersion($versionId);
	}

}
