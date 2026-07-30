<?php declare(strict_types = 1);

namespace ConditionalThrowsMethod;

use Exception;

class Service
{

	/**
	 * @param int $x
	 * @throws ($x is 0 ? Exception : void)
	 */
	public function inverse(int $x): float
	{
		if ($x === 0) {
			throw new Exception('Division by zero.');
		}

		return 1 / $x;
	}

	/**
	 * @param int $x
	 * @throws ($x is 0 ? Exception : void)
	 */
	public static function staticInverse(int $x): float
	{
		if ($x === 0) {
			throw new Exception('Division by zero.');
		}

		return 1 / $x;
	}

	/**
	 * @param int $x
	 * @throws ($x is 0 ? Exception : void)
	 */
	public function __construct(int $x)
	{
		if ($x === 0) {
			throw new Exception('Division by zero.');
		}
	}

	/**
	 * @template TKey of int|string
	 * @param TKey $key
	 * @throws (TKey is int ? void : Exception)
	 */
	public function lookup($key): void
	{
		if (is_string($key)) {
			throw new Exception('String keys are not supported.');
		}
	}

}

class Service2 extends Service
{

	public function inverse(int $y): float
	{
		if ($y === 0) {
			throw new Exception('Division by zero.');
		}

		return 1 / $y;
	}

}

class Caller
{

	/** @throws void */
	public function methodCallZero(Service $service): void
	{
		$service->inverse(0);
	}

	/** @throws void */
	public function methodCallNonZero(Service $service): void
	{
		$service->inverse(7);
	}

	/** @throws void */
	public function staticCallZero(): void
	{
		Service::staticInverse(0);
	}

	/** @throws void */
	public function staticCallNonZero(): void
	{
		Service::staticInverse(7);
	}

	/** @throws void */
	public function constructorZero(): void
	{
		new Service(0);
	}

	/** @throws void */
	public function constructorNonZero(): void
	{
		new Service(7);
	}

	/** @throws void */
	public function lookupInt(Service $service): void
	{
		$service->lookup(1);
	}

	/** @throws void */
	public function lookupString(Service $service): void
	{
		$service->lookup('foo');
	}

	/** @throws void */
	public function inheritedMethodCallZero(Service2 $service): void
	{
		$service->inverse(0);
	}

	/** @throws void */
	public function inheritedMethodCallNonZero(Service2 $service): void
	{
		$service->inverse(7);
	}

}
