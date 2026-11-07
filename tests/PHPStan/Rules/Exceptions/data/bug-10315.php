<?php declare(strict_types = 1);

namespace Bug10315;

class PhpfastcacheUnsupportedMethodException extends \Exception
{

}

trait DriverPoolAbstractTrait
{

	/**
	 * @throws PhpfastcacheUnsupportedMethodException
	 */
	protected function driverReadMultiple(): array
	{
		throw new PhpfastcacheUnsupportedMethodException();
	}

}

trait CacheItemPoolTrait
{

	public function getItems(): array
	{
		try {
			return $this->driverReadMultiple();
		} catch (PhpfastcacheUnsupportedMethodException $e) {
			return [];
		}
	}

}

class Redis
{

	use DriverPoolAbstractTrait, CacheItemPoolTrait;

	protected function driverReadMultiple(): array
	{
		return [];
	}

}

class Memcached
{

	use DriverPoolAbstractTrait, CacheItemPoolTrait;

}
