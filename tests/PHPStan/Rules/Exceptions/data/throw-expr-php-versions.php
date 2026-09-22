<?php // lint >= 8.0

namespace ThrowExprPhpVersions;

class Bar
{

	public function supportedInBranch(bool $b): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$b ? true : throw new \Exception();
		}
	}

	public function unsupportedInBranch(bool $b): void
	{
		if (PHP_VERSION_ID < 80000) {
			$b ? true : throw new \Exception();
		}
	}

	public function alwaysUnsupported(bool $b): void
	{
		$b ? true : throw new \Exception();
	}

}
