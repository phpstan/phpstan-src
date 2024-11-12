<?php

namespace Bug4434;

use function PHPStan\Testing\assertType;
use const PHP_MAJOR_VERSION;

class HelloWorld
{
	public function testSendEmailToLog(): void
	{
		foreach ([1] as $emailFile) {
			assertType('int<5, 8>', PHP_MAJOR_VERSION);
			assertType('int<5, 8>', \PHP_MAJOR_VERSION);
			if (PHP_MAJOR_VERSION === 7) {
				assertType('7', PHP_MAJOR_VERSION);
				assertType('7', \PHP_MAJOR_VERSION);
			} else {
				assertType('8|int<5, 6>', PHP_MAJOR_VERSION);
				assertType('8|int<5, 6>', \PHP_MAJOR_VERSION);
			}
		}
	}
}

class HelloWorld2
{
	public function testSendEmailToLog(): void
	{
		foreach ([1] as $emailFile) {
			assertType('int<5, 8>', PHP_MAJOR_VERSION);
			assertType('int<5, 8>', \PHP_MAJOR_VERSION);
			if (PHP_MAJOR_VERSION === 100) {
				assertType('*NEVER*', PHP_MAJOR_VERSION);
				assertType('*NEVER*', \PHP_MAJOR_VERSION);
			} else {
				assertType('int<5, 8>', PHP_MAJOR_VERSION);
				assertType('int<5, 8>', \PHP_MAJOR_VERSION);
			}
		}
	}
}
