<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Php\PhpVersion;

final class SignatureMapProviderFactory
{

	public function __construct(
		private PhpVersion $phpVersion,
		private FunctionSignatureMapProvider $functionSignatureMapProvider,
		private Php8SignatureMapProvider $php8SignatureMapProvider,
	)
	{
	}

	public function create(): SignatureMapProvider
	{
		if ($this->phpVersion->getVersionId() < 80000) {
			return $this->functionSignatureMapProvider;
		}

		return $this->php8SignatureMapProvider;
	}

}
