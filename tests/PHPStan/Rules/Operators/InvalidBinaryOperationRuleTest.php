<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidBinaryOperationRule>
 */
class InvalidBinaryOperationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidBinaryOperationRule(
			new ExprPrinter(new Printer()),
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-binary.php'], [
			[
				'Binary operation "-" between array and array results in an error.',
				12,
			],
			[
				'Binary operation "/" between 5 and 0 results in an error.',
				15,
			],
			[
				'Binary operation "%" between 5 and 0 results in an error.',
				16,
			],
			[
				'Binary operation "/" between int and 0.0 results in an error.',
				17,
			],
			[
				'Binary operation "+" between 1 and string results in an error.',
				20,
			],
			[
				'Binary operation "+" between 1 and \'blabla\' results in an error.',
				21,
			],
			[
				'Binary operation "+=" between array and \'foo\' results in an error.',
				28,
			],
			[
				'Binary operation "-=" between array and array results in an error.',
				34,
			],
			[
				'Binary operation "<<" between string and string results in an error.',
				47,
			],
			[
				'Binary operation ">>" between string and string results in an error.',
				48,
			],
			[
				'Binary operation ">>=" between string and string results in an error.',
				49,
			],
			[
				'Binary operation "<<=" between string and string results in an error.',
				59,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to \'5\' loses precision.',
				67,
			],
			[
				'Binary operation "&" between string and 5 results in an error.',
				69,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to \'5\' loses precision.',
				71,
			],
			[
				'Binary operation "|" between string and 5 results in an error.',
				73,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to \'5\' loses precision.',
				75,
			],
			[
				'Binary operation "^" between string and 5 results in an error.',
				77,
			],
			[
				'Binary operation "." between string and stdClass results in an error.',
				87,
			],
			[
				'Binary operation ".=" between string and stdClass results in an error.',
				91,
			],
			[
				'Binary operation "/" between 5 and 0|1 results in an error.',
				122,
			],
			[
				'Binary operation "." between array and \'xyz\' results in an error.',
				127,
			],
			[
				'Binary operation "." between list<string>|string and \'xyz\' results in an error.',
				134,
			],
			[
				'Binary operation "+" between (list<string>|string) and 1 results in an error.',
				136,
			],
			[
				'Binary operation "==" between stdClass and int results in an error.',
				156,
			],
			[
				'Binary operation "+" between stdClass and int results in an error.',
				157,
			],
			[
				'Binary operation "+" between non-empty-string and 10 results in an error.',
				184,
			],
			[
				'Binary operation "-" between non-empty-string and 10 results in an error.',
				185,
			],
			[
				'Binary operation "*" between non-empty-string and 10 results in an error.',
				186,
			],
			[
				'Binary operation "/" between non-empty-string and 10 results in an error.',
				187,
			],
			[
				'Binary operation "+" between 10 and non-empty-string results in an error.',
				189,
			],
			[
				'Binary operation "-" between 10 and non-empty-string results in an error.',
				190,
			],
			[
				'Binary operation "*" between 10 and non-empty-string results in an error.',
				191,
			],
			[
				'Binary operation "/" between 10 and non-empty-string results in an error.',
				192,
			],
			[
				'Binary operation "+" between string and 10 results in an error.',
				194,
			],
			[
				'Binary operation "-" between string and 10 results in an error.',
				195,
			],
			[
				'Binary operation "*" between string and 10 results in an error.',
				196,
			],
			[
				'Binary operation "/" between string and 10 results in an error.',
				197,
			],
			[
				'Binary operation "+" between 10 and string results in an error.',
				199,
			],
			[
				'Binary operation "-" between 10 and string results in an error.',
				200,
			],
			[
				'Binary operation "*" between 10 and string results in an error.',
				201,
			],
			[
				'Binary operation "/" between 10 and string results in an error.',
				202,
			],
			[
				'Binary operation "+" between class-string and 10 results in an error.',
				204,
			],
			[
				'Binary operation "-" between class-string and 10 results in an error.',
				205,
			],
			[
				'Binary operation "*" between class-string and 10 results in an error.',
				206,
			],
			[
				'Binary operation "/" between class-string and 10 results in an error.',
				207,
			],
			[
				'Binary operation "+" between 10 and class-string results in an error.',
				209,
			],
			[
				'Binary operation "-" between 10 and class-string results in an error.',
				210,
			],
			[
				'Binary operation "*" between 10 and class-string results in an error.',
				211,
			],
			[
				'Binary operation "/" between 10 and class-string results in an error.',
				212,
			],
			[
				'Binary operation "+" between literal-string and 10 results in an error.',
				214,
			],
			[
				'Binary operation "-" between literal-string and 10 results in an error.',
				215,
			],
			[
				'Binary operation "*" between literal-string and 10 results in an error.',
				216,
			],
			[
				'Binary operation "/" between literal-string and 10 results in an error.',
				217,
			],
			[
				'Binary operation "+" between 10 and literal-string results in an error.',
				219,
			],
			[
				'Binary operation "-" between 10 and literal-string results in an error.',
				220,
			],
			[
				'Binary operation "*" between 10 and literal-string results in an error.',
				221,
			],
			[
				'Binary operation "/" between 10 and literal-string results in an error.',
				222,
			],
			[
				'Binary operation "+" between int and array{} results in an error.',
				259,
			],
		]);
	}

	public function testBug2964(): void
	{
		$this->analyse([__DIR__ . '/data/bug2964.php'], []);
	}

	public function testBug3515(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3515.php'], []);
	}

	public function testBug8288(): void
	{
		$this->analyse([__DIR__ . '/data/bug8288.php'], [
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to float loses precision.',
				12,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to numeric-string loses precision.',
				13,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				14,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to float loses precision.',
				15,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to numeric-string loses precision.',
				16,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				17,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to float loses precision.',
				18,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float|int<0, 15> to 15 loses precision.',
				24,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				25,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				26,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to float loses precision.',
				42,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to numeric-string loses precision.',
				43,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				44,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to float loses precision.',
				45,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to numeric-string loses precision.',
				46,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				47,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to float loses precision.',
				48,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float|int<0, 15> to 15 loses precision.',
				54,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				55,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				56,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to float loses precision.',
				72,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to numeric-string loses precision.',
				73,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				74,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to float loses precision.',
				75,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to numeric-string loses precision.',
				76,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				77,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to float loses precision.',
				78,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float|int<0, 15> to 15 loses precision.',
				84,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				85,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				86,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to float loses precision.',
				102,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to numeric-string loses precision.',
				103,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				104,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to float loses precision.',
				105,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to numeric-string loses precision.',
				106,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				107,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to float loses precision.',
				108,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to numeric-string loses precision.',
				109,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float|int<0, 15> to 15 loses precision.',
				115,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				116,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				117,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to float loses precision.',
				133,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to numeric-string loses precision.',
				134,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				135,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to float loses precision.',
				136,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to numeric-string loses precision.',
				137,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				138,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to float loses precision.',
				139,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to numeric-string loses precision.',
				140,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float|int<0, 15> to 15 loses precision.',
				146,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				147,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				148,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to float loses precision.',
				164,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from int to numeric-string loses precision.',
				165,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				166,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to float loses precision.',
				167,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to numeric-string loses precision.',
				168,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				169,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to float loses precision.',
				170,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to numeric-string loses precision.',
				171,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float|int<0, 15> to 15 loses precision.',
				177,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				178,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to 15 loses precision.',
				179,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/invalid-binary-nullsafe.php'], [
			[
				'Binary operation "+" between array|null and \'2\' results in an error.',
				12,
			],
		]);
	}
}
