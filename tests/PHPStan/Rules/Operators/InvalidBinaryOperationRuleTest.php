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
				'Binary operation "&" between string and 5 results in an error.',
				69,
			],
			[
				'Binary operation "|" between string and 5 results in an error.',
				73,
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

	public function testBug8827(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-8827.php'], []);
	}

	public function testBug8288(): void
	{
		$this->analyse([__DIR__ . '/data/bug8288.php'], [
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				17,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				18,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				19,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				20,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float to int loses precision.',
				21,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				22,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				23,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from numeric-string to int loses precision.',
				24,
			],
			[
				'Binary operation "%" between int and string results in an error.',
				26,
			],
			[
				'Binary operation "%" between int and Stringable results in an error.',
				27,
			],
			[
				'Binary operation "%" between int and array results in an error.',
				28,
			],
			[
				'Binary operation "%" between float and string results in an error.',
				29,
			],
			[
				'Binary operation "%" between float and Stringable results in an error.',
				30,
			],
			[
				'Binary operation "%" between float and array results in an error.',
				31,
			],
			[
				'Binary operation "%" between string and int results in an error.',
				32,
			],
			[
				'Binary operation "%" between string and float results in an error.',
				33,
			],
			[
				'Binary operation "%" between string and string results in an error.',
				34,
			],
			[
				'Binary operation "%" between string and numeric-string results in an error.',
				35,
			],
			[
				'Binary operation "%" between string and Stringable results in an error.',
				36,
			],
			[
				'Binary operation "%" between string and array results in an error.',
				37,
			],
			[
				'Binary operation "%" between numeric-string and string results in an error.',
				38,
			],
			[
				'Binary operation "%" between numeric-string and Stringable results in an error.',
				39,
			],
			[
				'Binary operation "%" between numeric-string and array results in an error.',
				40,
			],
			[
				'Binary operation "%" between Stringable and int results in an error.',
				41,
			],
			[
				'Binary operation "%" between Stringable and float results in an error.',
				42,
			],
			[
				'Binary operation "%" between Stringable and string results in an error.',
				43,
			],
			[
				'Binary operation "%" between Stringable and numeric-string results in an error.',
				44,
			],
			[
				'Binary operation "%" between Stringable and Stringable results in an error.',
				45,
			],
			[
				'Binary operation "%" between Stringable and array results in an error.',
				46,
			],
			[
				'Binary operation "%" between array and int results in an error.',
				47,
			],
			[
				'Binary operation "%" between array and float results in an error.',
				48,
			],
			[
				'Binary operation "%" between array and string results in an error.',
				49,
			],
			[
				'Binary operation "%" between array and numeric-string results in an error.',
				50,
			],
			[
				'Binary operation "%" between array and Stringable results in an error.',
				51,
			],
			[
				'Binary operation "%" between array and array results in an error.',
				52,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from float|int<0, 15> to int loses precision.',
				58,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to int loses precision.',
				59,
			],
			[
				'Deprecated in PHP 8.1: Implicit conversion from 6.0625 to int loses precision.',
				60,
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
