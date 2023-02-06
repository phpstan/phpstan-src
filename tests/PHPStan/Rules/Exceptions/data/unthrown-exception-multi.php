<?php

namespace UnthrownExceptionMulti;

class TestMultiCatchContainingDeadExceptions
{

	public function doFoo()
	{
		try {
			throw new \RuntimeException();
		} catch (\RuntimeException | \LogicException $t) { // logic not thrown

		}
	}

	public function doBar()
	{
		try {
			$this->throwLogicRangeJsonExceptions();

		} catch (\JsonException $t) {

		} catch (\RangeException | \LogicException $t) {

		}
	}

	public function doBaz()
	{
		try {
			$this->throwLogicRangeJsonExceptions();

		} catch (\JsonException $t) {

		} catch (\RangeException | \LogicException | \OverflowException $t) { // overflow not thrown

		}
	}

	public function doBag()
	{
		try {
			$this->throwLogicRangeJsonExceptions();

		} catch (\RuntimeException $t) {

		} catch (\LogicException | \JsonException $t) {

		}
	}

	public function doZag()
	{
		try {
			throw new \RangeException();

		} catch (\RuntimeException | \JsonException $t) { // json not thrown

		}
	}

	public function doBal()
	{
		try {
			$this->throwLogicRangeJsonExceptions();

		} catch (\RuntimeException | \JsonException $t) {

		} catch (\InvalidArgumentException $t) {

		}
	}

	public function doBap()
	{
		try {
			$this->throwLogicRangeJsonExceptions();

		} catch (\InvalidArgumentException $t) {

		} catch (\RuntimeException | \JsonException $t) {

		}
	}

	public function doZaz()
	{
		try {
			\ThrowPoints\Helpers\maybeThrows();
		} catch (\LogicException $e) {

		}
	}

	public function doZab()
	{
		try {
			\ThrowPoints\Helpers\maybeThrows();
		} catch (\InvalidArgumentException | \LogicException $e) {

		}
	}

	/**
	 * @throws \RangeException
	 * @throws \LogicException
	 * @throws \JsonException
	 */
	private function throwLogicRangeJsonExceptions(): void
	{

	}


}
