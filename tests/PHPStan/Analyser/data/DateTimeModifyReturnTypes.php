<?php declare(strict_types = 1);

namespace DateTimeModifyReturnTypes;

use DateTime;
use DateTimeImmutable;
use function PHPStan\Testing\assertType;

class Foo
{
	public function modify(DateTime $datetime, DateTimeImmutable $dateTimeImmutable, string $modify): void {
		assertType('(DateTime|false)', $datetime->modify($modify));
		assertType('(DateTimeImmutable|false)', $dateTimeImmutable->modify($modify));
	}

	/**
	 * @param '+1 day'|'+2 day' $modify
	 */
	public function modifyWithValidConstant(DateTime $datetime, DateTimeImmutable $dateTimeImmutable, string $modify): void {
		assertType('DateTime', $datetime->modify($modify));
		assertType('DateTimeImmutable', $dateTimeImmutable->modify($modify));
	}

	/**
	 * @param 'kewk'|'koko' $modify
	 */
	public function modifyWithInvalidConstant(DateTime $datetime, DateTimeImmutable $dateTimeImmutable, string $modify): void {
		assertType('false', $datetime->modify($modify));
		assertType('false', $dateTimeImmutable->modify($modify));
	}

	/**
	 * @param '+1 day'|'koko' $modify
	 */
	public function modifyWithBothConstant(DateTime $datetime, DateTimeImmutable $dateTimeImmutable, string $modify): void {
		assertType('(DateTime|false)', $datetime->modify($modify));
		assertType('(DateTimeImmutable|false)', $dateTimeImmutable->modify($modify));
	}

}
