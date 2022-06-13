<?php declare(strict_types = 1);

namespace DateTimeCreateDynamicReturnTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	public function createDynamic(string $datetime): void {
		assertType('DateTime|false', date_create($datetime));
		assertType('DateTimeImmutable|false', date_create_immutable($datetime));
	}

	public function staticInvalidDatetime(): void {
		assertType('false', date_create('202/20'));
		assertType('false', date_create_immutable('202/20'));
	}

	public function staticValidStrings(): void {
		assertType('DateTime', date_create('2020-10-12'));
		assertType('DateTimeImmutable', date_create_immutable('2020-10-12'));
	}

	public function localVariables(): void {
		$datetime = '2020-10-12';

		assertType('DateTime', date_create($datetime));
		assertType('DateTimeImmutable', date_create_immutable($datetime));
	}

}
