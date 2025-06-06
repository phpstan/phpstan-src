<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInFunctionThrowsRule>
 */
class MissingCheckedExceptionInFunctionThrowsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInFunctionThrowsRule(
			new MissingCheckedExceptionInThrowsCheck(new DefaultExceptionTypeResolver(
				self::createReflectionProvider(),
				[],
				[ShouldNotHappenException::class],
				[],
				[],
			)),
		);
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/missing-exception-function-throws.php';
		$this->analyse([__DIR__ . '/data/missing-exception-function-throws.php'], [
			[
				'Function MissingExceptionFunctionThrows\doBaz() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				20,
			],
			[
				'Function MissingExceptionFunctionThrows\doLorem() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				26,
			],
			[
				'Function MissingExceptionFunctionThrows\doLorem2() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				31,
			],
			[
				'Function MissingExceptionFunctionThrows\doBar2() throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag.',
				51,
			],
			[
				'Function MissingExceptionFunctionThrows\doBar3() throws checked exception LogicException but it\'s missing from the PHPDoc @throws tag.',
				57,
			],
		]);
	}

}
