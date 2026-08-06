<?php declare(strict_types = 1);

namespace Bug12201;

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
