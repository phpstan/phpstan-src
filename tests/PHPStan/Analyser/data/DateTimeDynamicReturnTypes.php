<?php declare(strict_types=1);

namespace DateTimeDynamicReturnTypes;

use DateTime;
use DateTimeImmutable;
use function date_create_from_format;
use function PHPStan\Testing\assertType;

class Foo
{
	public function createDynamic(string $format, string $datetime): void {
		assertType('DateTime|false', date_create_from_format($format, $datetime));
		assertType('DateTimeImmutable|false', date_create_immutable_from_format($format, $datetime));

		assertType('DateTime|false', DateTime::createFromFormat($format, $datetime));
		assertType('DateTimeImmutable|false', DateTimeImmutable::createFromFormat($format, $datetime));
	}

	public function staticInvalidFormat(): void {
		assertType('false', date_create_from_format('Foobar', '2022-02-20'));
		assertType('false', date_create_immutable_from_format('Foobar', '2022-02-20'));

		assertType('false', DateTime::createFromFormat('Foobar', '2022-02-20'));
		assertType('false', DateTimeImmutable::createFromFormat('Foobar', '2022-02-20'));
	}

	public function staticInvalidDatetime(): void {
		assertType('false', date_create_from_format('Y-m-d', '2022/02/20'));
		assertType('false', date_create_immutable_from_format('Y-m-d', '2022/02/20'));

		assertType('false', DateTime::createFromFormat('Y-m-d', '2022/02/20'));
		assertType('false', DateTimeImmutable::createFromFormat('Y-m-d', '2022/02/20'));
	}

	public function staticValidStrings(): void {
		assertType('DateTime', date_create_from_format('Y-m-d', '2020-10-12'));
		assertType('DateTimeImmutable', date_create_immutable_from_format('Y-m-d', '2020-10-12'));

		assertType('DateTime', DateTime::createFromFormat('Y-m-d', '2020-10-12'));
		assertType('DateTimeImmutable', DateTimeImmutable::createFromFormat('Y-m-d', '2020-10-12'));
	}
}
