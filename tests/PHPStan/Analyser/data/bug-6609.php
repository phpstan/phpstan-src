<?php

namespace Bug6609;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * This method should return the same type as a parameter passed.
	 *
	 * @template T of \DateTime|\DateTimeImmutable
	 *
	 * @param T $date
	 *
	 * @return T
	 */
	function modify(\DateTimeInterface $date) {
		$date = $date->modify('+1 day');
		assertType('T of DateTime|DateTimeImmutable (method Bug6609\Foo::modify(), argument)', $date);

		return $date;
	}

	/**
	 * This method should return the same type as a parameter passed.
	 *
	 * @template T of \DateTime|\DateTimeImmutable
	 *
	 * @param T $date
	 *
	 * @return T
	 */
	function modify2(\DateTimeInterface $date) {
		$date = $date->modify('invalidd');
		assertType('false', $date);

		return $date;
	}

	/**
	 * This method should return the same type as a parameter passed.
	 *
	 * @template T of \DateTime|\DateTimeImmutable
	 *
	 * @param T $date
	 *
	 * @return T
	 */
	function modify3(\DateTimeInterface $date, string $s) {
		$date = $date->modify($s);
		assertType('(DateTime|DateTimeImmutable|false)', $date);

		return $date;
	}

}
