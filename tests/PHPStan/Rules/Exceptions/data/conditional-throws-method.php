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

}
