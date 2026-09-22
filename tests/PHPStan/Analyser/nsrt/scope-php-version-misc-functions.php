<?php declare(strict_types = 1);

namespace ScopePhpVersionMiscFunctions;

use DateInterval;
use DateTime;
use PHPStan\Reflection\ClassReflection;
use function PHPStan\Testing\assertType;

class Foo
{

	public function dateTimeModify(DateTime $dateTime): void
	{
		if (PHP_VERSION_ID >= 80300) {
			assertType('*NEVER*', $dateTime->modify('invalid'));
		}

		if (PHP_VERSION_ID < 80300) {
			assertType('false', $dateTime->modify('invalid'));
		}
	}

	public function dateIntervalCreateFromDateString(): void
	{
		if (PHP_VERSION_ID >= 80300) {
			assertType('*NEVER*', DateInterval::createFromDateString('invalid'));
		}

		if (PHP_VERSION_ID < 80300) {
			assertType('false', DateInterval::createFromDateString('invalid'));
		}
	}

	public function filterInputWithInvalidType(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', filter_input(999, 'foo'));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('null', filter_input(999, 'foo'));
		}
	}

	public function mbStrlenWithUnsupportedEncoding(string $s): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', mb_strlen($s, 'unsupported-encoding'));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', mb_strlen($s, 'unsupported-encoding'));
		}
	}

	public function mbChrWithUnsupportedEncoding(int $i): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', mb_chr($i, 'unsupported-encoding'));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', mb_chr($i, 'unsupported-encoding'));
		}
	}

	public function opensslCipherIvLength(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('false', openssl_cipher_iv_length('unknown-cipher'));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('int|false', openssl_cipher_iv_length('unknown-cipher'));
		}
	}

	public function pregMatchWithNoCaptureModifier(string $subject): void
	{
		if (PHP_VERSION_ID >= 80200) {
			if (preg_match('/(?<name>\\w+)(\\d+)/n', $subject, $matches) === 1) {
				assertType('array{0: non-falsy-string, name: non-empty-string, 1: non-empty-string}', $matches);
			}
		}

		if (PHP_VERSION_ID < 80200) {
			if (preg_match('/(?<name>\\w+)(\\d+)/n', $subject, $matches2) === 1) {
				assertType('array{0: non-falsy-string, name: non-empty-string, 1: non-empty-string, 2: numeric-string}', $matches2);
			}
		}
	}

	public function nativeReflection(ClassReflection $classReflection): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass|PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionEnum', $classReflection->getNativeReflection());
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass', $classReflection->getNativeReflection());
		}
	}

	public function pdoConnect(): void
	{
		if (PHP_VERSION_ID >= 80400) {
			assertType('PDO\\Mysql', \PDO::connect('mysql:host=localhost'));
		}

		if (PHP_VERSION_ID < 80400) {
			assertType('PDO', \PDO::connect('mysql:host=localhost'));
		}
	}

}
