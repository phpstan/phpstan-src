<?php declare(strict_types = 1);

namespace IntdivReturnType;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param int<0, max> $nonNegative
	 * @param int<1, max> $positive
	 * @param int<min, 0> $nonPositive
	 * @param int<min, -1> $negative
	 * @param int<-5, 5> $small
	 * @param int<2, 4> $divisor
	 * @param 1|2|4 $union
	 */
	public function signs($nonNegative, $positive, $nonPositive, $negative, $small, $divisor, $union, int $any): void
	{
		assertType('int<0, max>', intdiv($nonNegative, $positive));
		assertType('int<min, 0>', intdiv($nonNegative, $negative));
		assertType('int<min, 0>', intdiv($nonPositive, $positive));
		assertType('int<0, max>', intdiv($nonPositive, $negative));
		assertType('int<0, max>', intdiv($positive, $positive));

		assertType('int<-2, 2>', intdiv($small, $divisor));
		assertType('int<-5, 5>', intdiv($small, $small));
		assertType('int<-5, 5>', intdiv($small, $positive));
		assertType('int<-5, 5>', intdiv($small, $negative));

		assertType('int', intdiv($any, $positive));
		assertType('int', intdiv($nonNegative, $any));
		assertType('int', intdiv($any, $any));

		assertType('0|1|2', intdiv($union, 2));
		assertType('int<0, max>', intdiv($nonNegative, $union));
	}

	/**
	 * @param int<-5, 5> $small
	 * @param int<0, max> $nonNegative
	 */
	public function constants($small, $nonNegative): void
	{
		assertType('3', intdiv(10, 3));
		assertType('-3', intdiv(-10, 3));
		assertType('-3', intdiv(10, -3));
		assertType('3', intdiv(-10, -3));
		assertType('0', intdiv(1, 2));

		assertType('int<-5, 5>', intdiv($small, 1));
		assertType('int<-5, 5>', intdiv($small, -1));
		assertType('int<-2, 2>', intdiv($small, 2));
		assertType('int<-2, 2>', intdiv($small, -2));
		assertType('int<0, max>', intdiv($nonNegative, 1));
		assertType('int<min, 0>', intdiv($nonNegative, -1));

		// always throws, no value is ever produced
		assertType('int', intdiv($small, 0));
		assertType('int', intdiv(PHP_INT_MIN, -1));
	}

}
