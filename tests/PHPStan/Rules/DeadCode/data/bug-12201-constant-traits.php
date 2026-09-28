<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug12201Constant;

// Stands for a dependency shipped in vendor/: bug-12201-constant.php uses these traits, but
// this file is never passed to analyse(), so PHPStan does not traverse the trait bodies.

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
