<?php declare(strict_types = 1);

namespace Bug12201;

// Stands for a dependency shipped in vendor/: bug-12201.php uses these traits, but
// this file is never passed to analyse(), so PHPStan does not traverse the trait bodies.

trait KernelTrait
{
	/**
	 * @return string[]
	 */
	private function getAllowedEnvs(): array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	protected function getKernelParameters(): array
	{
		return $this->getAllowedEnvs();
	}
}

trait MicroKernelTrait
{
	use KernelTrait;
}
