<?php declare(strict_types = 1);

namespace Bug12201Property;

trait KernelTrait
{

	/** @var list<string> */
	private array $allowedEnvs = [];

	/**
	 * @return list<string>
	 */
	protected function getAllowedEnvs(): array
	{
		return $this->allowedEnvs;
	}

}

trait MicroKernelTrait
{

	use KernelTrait;

}

trait StaticKernelTrait
{

	/** @var list<string> */
	private static array $staticAllowedEnvs = [];

	/**
	 * @return list<string>
	 */
	protected static function getStaticAllowedEnvs(): array
	{
		return self::$staticAllowedEnvs;
	}

}
