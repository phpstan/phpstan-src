<?php // lint >= 8.0

namespace DateFormatReturnType;

use function PHPStan\Testing\assertType;

function (string $s): void {
	assertType('\'\'', date(''));
	assertType('string', date($s));
	assertType('non-falsy-string', date('D'));
	assertType('numeric-string', date('Y'));
	assertType('numeric-string', date('Ghi'));
};

function (\DateTime $dt, string $s): void {
	assertType('\'\'', date_format($dt, ''));
	assertType('string', date_format($dt, $s));
	assertType('non-falsy-string', date_format($dt, 'D'));
	assertType('numeric-string', date_format($dt, 'Y'));
	assertType('numeric-string', date_format($dt, 'Ghi'));
};

function (\DateTimeInterface $dt, string $s): void {
	assertType('\'\'', $dt->format(''));
	assertType('string', $dt->format($s));
	assertType('non-falsy-string', $dt->format('D'));
	assertType('numeric-string', $dt->format('Y'));
	assertType('numeric-string', $dt->format('Ghi'));
};

function (\DateTime $dt, string $s): void {
	assertType('\'\'', $dt->format(''));
	assertType('string', $dt->format($s));
	assertType('non-falsy-string', $dt->format('D'));
	assertType('numeric-string', $dt->format('Y'));
	assertType('numeric-string', $dt->format('Ghi'));
};

function (\DateTimeImmutable $dt, string $s): void {
	assertType('\'\'', $dt->format(''));
	assertType('string', $dt->format($s));
	assertType('non-falsy-string', $dt->format('D'));
	assertType('numeric-string', $dt->format('Y'));
	assertType('numeric-string', $dt->format('Ghi'));
};

function (?\DateTimeImmutable $d): void {
	assertType('DateTimeImmutable', $d->modify('+1 day'));
};

function (?\DateTimeImmutable $d): void {
	assertType('DateTimeImmutable|null', $d?->modify('+1 day'));
};

class Foo extends \DateTimeImmutable {}
class Bar {
	/** @return string */
	public function modify($string) {}
}
class Bar2 {
	/** @return string|false */
	public function modify($string) {}
}

function foo(Foo|Bar $d): void {
	assertType('DateFormatReturnType\Foo|string', $d->modify('+1 day'));
};

function foo2(Foo|Bar2 $d): void {
	assertType('DateFormatReturnType\Foo|string|false', $d->modify('+1 day'));
};
