<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10315;

class MyException extends \RuntimeException
{
}

trait DriverPoolAbstractTrait
{
	/**
	 * @throws MyException
	 */
	protected function driverReadMultiple(): array
	{
		throw new MyException();
	}
}

trait CacheItemPoolTrait
{
	public function getItems(): array
	{
		try {
			$result = $this->driverReadMultiple();
		} catch (MyException) {
			$result = [];
		}

		return $result;
	}
}

// Scenario A: base uses DriverPoolAbstractTrait, child uses CacheItemPoolTrait and overrides
abstract class AbstractPoolA
{
	use DriverPoolAbstractTrait;
}

class RedisDriverA extends AbstractPoolA
{
	use CacheItemPoolTrait;

	protected function driverReadMultiple(): array
	{
		return ['key' => 'value'];
	}
}

// Scenario B: class uses both traits and overrides the method directly
class DirectDriverB
{
	use DriverPoolAbstractTrait;
	use CacheItemPoolTrait;

	protected function driverReadMultiple(): array
	{
		return ['key' => 'value'];
	}
}
