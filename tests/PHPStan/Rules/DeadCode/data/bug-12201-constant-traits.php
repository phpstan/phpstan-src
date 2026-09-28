<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug12201Constant;

trait KernelTrait
{

	private const ALLOWED_ENVS = ['prod', 'dev', 'test'];

	/**
	 * @return list<string>
	 */
	protected function getAllowedEnvs(): array
	{
		return self::ALLOWED_ENVS;
	}

}

trait MicroKernelTrait
{

	use KernelTrait;

}
