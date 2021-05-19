<?php

namespace UnthrownException;

class Foo
{

	public function doFoo(): void
	{
		try {
			$foo = 1;
		} catch (\Throwable $e) {
			// pass
		}
	}

	public function doBar(): void
	{
		try {
			$foo = 1;
		} catch (\Exception $e) {
			// pass
		}
	}

	/** @throws \InvalidArgumentException */
	public function throwIae(): void
	{

	}

	public function doBaz(): void
	{
		try {
			$this->throwIae();
		} catch (\InvalidArgumentException $e) {

		} catch (\Exception $e) {
			// dead
		} catch (\Throwable $e) {
			// not dead
		}
	}

	public function doLorem(): void
	{
		try {
			$this->throwIae();
		} catch (\RuntimeException $e) {
 			// dead
		} catch (\Throwable $e) {

		}
	}

	public function doIpsum(): void
	{
		try {
			$this->throwIae();
		} catch (\Throwable $e) {

		}
	}

	public function doDolor(): void
	{
		try {
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {

		} catch (\Throwable $e) {

		}
	}

	public function doSit(): void
	{
		try {
			try {
				\ThrowPoints\Helpers\maybeThrows();
			} catch (\InvalidArgumentException $e) {

			}
		} catch (\InvalidArgumentException $e) {

		}
	}

	/**
	 * @throws \InvalidArgumentException
	 * @throws \DomainException
	 */
	public function doAmet()
	{

	}

	public function doAmet1()
	{
		try {
			$this->doAmet();
		} catch (\InvalidArgumentException $e) {

		} catch (\DomainException $e) {

		} catch (\Throwable $e) {
			// not dead
		}
	}

	public function doAmet2()
	{
		try {
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {

		} catch (\DomainException $e) {
			// dead
		} catch (\Throwable $e) {
			// dead
		}
	}

	public function doConsecteur()
	{
		try {
			if (false) {

			} elseif ($this->doAmet()) {

			}
		} catch (\InvalidArgumentException $e) {

		}
	}

}

class InlineThrows
{

	public function doFoo()
	{
		try {
			/** @throws \InvalidArgumentException */
			echo 1;
		} catch (\InvalidArgumentException $e) {

		}
	}

	public function doBar()
	{
		try {
			/** @throws \InvalidArgumentException */
			$i = 1;
		} catch (\InvalidArgumentException $e) {

		}
	}

}

class TestDateTime
{

	public function doFoo(): void
	{
		try {
			new \DateTime();
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \DateTime('now');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $s): void
	{
		try {
			new \DateTime($s);
		} catch (\Exception $e) {

		}
	}

}

class TestDateInterval
{

	public function doFoo(): void
	{
		try {
			new \DateInterval('invalid format');
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \DateInterval('P10D');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $s): void
	{
		try {
			new \DateInterval($s);
		} catch (\Exception $e) {

		}
	}

}

class TestIntdiv
{

	public function doFoo(): void
	{
		try {
			intdiv(1, 1);
			intdiv(1, -1);
		} catch (\ArithmeticError $e) {

		}
		try {
			intdiv(PHP_INT_MIN, -1);
		} catch (\ArithmeticError $e) {

		}
		try {
			intdiv(1, 0);
		} catch (\ArithmeticError $e) {

		}
	}

	public function doBar(int $int): void
	{
		try {
			intdiv($int, 1);
		} catch (\ArithmeticError $e) {

		}
		try {
			intdiv($int, -1);
		} catch (\ArithmeticError $e) {

		}
	}

	public function doBaz(int $int): void
	{
		try {
			intdiv(1, $int);
		} catch (\ArithmeticError $e) {

		}
		try {
			intdiv(PHP_INT_MIN, $int);
		} catch (\ArithmeticError $e) {

		}
	}

}

class TestSimpleXMLElement
{

	public function doFoo(): void
	{
		try {
			new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \SimpleXMLElement('foo');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $string): void
	{
		try {
			new \SimpleXMLElement($string);
		} catch (\Exception $e) {

		}
	}

}
