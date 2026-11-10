<?php // lint >= 8.1

namespace DeadCatchFirstClassCallables;

class Foo
{

	public function doFoo(): void
	{
		try {
			$this->doBar();
		} catch (\InvalidArgumentException $e) {

		}
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doBar(): void
	{
		throw new \InvalidArgumentException();
	}

	public function doBaz(): void
	{
		try {
			$this->doBar(...);
		} catch (\InvalidArgumentException $e) {

		}
	}

	public function doJson(string $s): void
	{
		$decode = json_decode(...);
		try {
			$decode($s, false, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {

		}

		try {
			$decode($s);
		} catch (\JsonException $e) {

		}
	}

}
