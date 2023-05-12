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

	public function someThrowableTest(): void
	{
		try {
			$this->throwIae();
		} catch (\InvalidArgumentException | \Exception $e) {

		} catch (\Throwable $e) {

		}
	}

	public function someThrowableTest2(): void
	{
		try {
			$this->throwIae();
		} catch (\RuntimeException | \Throwable $e) {
			// IAE is not runtime, dead
		} catch (\Throwable $e) {

		}
	}

	public function someThrowableTest3(): void
	{
		try {
			$this->throwIae();
		} catch (\Throwable $e) {

		} catch (\Throwable $e) {

		}
	}


	public function someThrowableTest4(): void
	{
		try {
			$this->throwIae();
		} catch (\Throwable $e) {

		} catch (\InvalidArgumentException $e) {

		}
	}

	public function someThrowableTest5(): void
	{
		try {
			$this->throwIae();
		} catch (\InvalidArgumentException $e) {

		} catch (\InvalidArgumentException $e) {

		}
	}

	public function someThrowableTest6(): void
	{
		try {
			$this->throwIae();
		} catch (\InvalidArgumentException | \Exception $e) {
			// catch can be simplified, this is not reported
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

	/** @throws \InvalidArgumentException */
	public function throwIae(): void
	{

	}


}
