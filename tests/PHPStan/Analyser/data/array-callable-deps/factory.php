<?php declare(strict_types = 1);

namespace ArrayCallableDeps;

class Factory
{
	public static function create(): \ArrayCallableDeps\FactoryResult
	{
		return new \ArrayCallableDeps\FactoryResult();
	}
}

class OtherFactory
{
	public static function create(): \ArrayCallableDeps\FactoryResult
	{
		return new \ArrayCallableDeps\FactoryResult();
	}
}
