<?php

namespace MissingExceptionMethodThrows;

class Foo
{

	/** @throws \InvalidArgumentException */
	public function doFoo(): void
	{
		throw new \InvalidArgumentException(); // ok
	}

	/** @throws \LogicException */
	public function doBar(): void
	{
		throw new \InvalidArgumentException(); // ok
	}

	/** @throws \RuntimeException */
	public function doBaz(): void
	{
		throw new \InvalidArgumentException(); // error
	}

	/** @throws \RuntimeException */
	public function doLorem(): void
	{
		throw new \InvalidArgumentException(); // error
	}

	public function doLorem2(): void
	{
		throw new \InvalidArgumentException(); // error
	}

	public function doLorem3(): void
	{
		try {
			throw new \InvalidArgumentException(); // ok
		} catch (\InvalidArgumentException $e) {

		}
	}

	public function doIpsum(): void
	{
		throw new \PHPStan\ShouldNotHappenException(); // ok
	}

	public function doDolor(): void
	{
		try {
			doFoo();
		} catch (\Throwable $e) {
			throw $e;
		}
	}

	public function doSit(): void
	{
		try {
			$this->throwsInterface();
		} catch (\Throwable $e) {

		}
	}

	public function doSit2(): void
	{
		try {
			$this->throwsInterface();
		} catch (\InvalidArgumentException $e) {

		} catch (\Throwable $e) {

		}
	}

	/**
	 * @throws \ExtendsThrowable\ExtendsThrowable
	 */
	private function throwsInterface(): void
	{

	}

	public function dateTimeZoneDoesNotThrow(): void
	{
		new \DateTimeZone('UTC');
	}

	public function dateTimeZoneDoesThrows(string $tz): void
	{
		new \DateTimeZone($tz);
	}

	public function dateTimeZoneDoesNotThrowCaseInsensitive(): void
	{
		new \DaTetImezOnE('UTC');
	}

	public function dateIntervalDoesThrows(string $i): void
	{
		new \DateInterval($i);
	}

	public function dateIntervalDoeNotThrow(): void
	{
		new \DateInterval('P7D');
	}

	public function dateTimeModifyDoeNotThrow(\DateTime $dt, \DateTimeImmutable $dti): void
	{
		$dt->modify('+1 day');
		$dti->modify('+1 day');
	}

	public function dateTimeModifyDoesThrows(\DateTime $dt, \DateTimeImmutable $dti, string $m): void
	{
		$dt->modify($m);
		$dti->modify($m);
	}

}
