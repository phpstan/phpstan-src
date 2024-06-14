<?php

namespace Bug4434;

use function PHPStan\Testing\assertType;
use const PHP_MAJOR_VERSION;

class HelloWorld
{
	public function testSendEmailToLog(): void
	{
		foreach ([1] as $emailFile) {
			assertType('int<5, max>', PHP_MAJOR_VERSION);
			assertType('int<5, max>', \PHP_MAJOR_VERSION);
			if (PHP_MAJOR_VERSION === 7) {
				assertType('7', PHP_MAJOR_VERSION);
				assertType('7', \PHP_MAJOR_VERSION);
			} else {
				assertType('int<5, 6>|int<8, max>', PHP_MAJOR_VERSION);
				assertType('int<5, 6>|int<8, max>', \PHP_MAJOR_VERSION);
			}
		}
	}
}

class HelloWorld2
{
	public function testSendEmailToLog(): void
	{
		foreach ([1] as $emailFile) {
			assertType('int<5, max>', PHP_MAJOR_VERSION);
			assertType('int<5, max>', \PHP_MAJOR_VERSION);
			if (PHP_MAJOR_VERSION === 100) {
				assertType('100', PHP_MAJOR_VERSION);
				assertType('100', \PHP_MAJOR_VERSION);
			} else {
				assertType('int<5, 99>|int<101, max>', PHP_MAJOR_VERSION);
				assertType('int<5, 99>|int<101, max>', \PHP_MAJOR_VERSION);
			}
		}
	}
}
