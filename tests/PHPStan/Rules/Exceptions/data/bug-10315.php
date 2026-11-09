<?php declare(strict_types = 1);

namespace Bug10315;

trait DriverPoolAbstractTrait
{
	/**
     * @return array<mixed>
     */
    protected function driverReadMultiple(): array
    {
		try {
			return $this->doThings();
		} catch (\Exception) {
			return [];
		}
    }
}

class Foo
{
	use DriverPoolAbstractTrait;

	/**
     * @return array<mixed>
	 * @throws void
     */
    public function doThings(): array
	{
		return [];
	}
}

class Foo2
{
	use DriverPoolAbstractTrait;

	/**
	 * @throws \Exception
     */
    public function doThings(): never
	{
		throw new \Exception();
	}
}
