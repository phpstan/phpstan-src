<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CaughtExceptionExistenceRule>
 */
class CaughtExceptionExistenceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new CaughtExceptionExistenceRule(
			$broker,
			new ClassCaseSensitivityCheck($broker, true),
			true,
		);
	}

	public function testCheckCaughtException(): void
	{
		$this->analyse([__DIR__ . '/data/catch.php'], [
			[
				'Caught class TestCatch\FooCatch is not an exception.',
				17,
			],
			[
				'Caught class FooCatchException not found.',
				29,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',

			],
			[
				'Class TestCatch\MyCatchException referenced with incorrect case: TestCatch\MyCatchEXCEPTION.',
				41,
			],
		]);
	}

	public function testClassExists(): void
	{
		$this->analyse([__DIR__ . '/data/class-exists.php'], []);
	}

	public function testBug3690(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3690.php'], []);
	}

}
