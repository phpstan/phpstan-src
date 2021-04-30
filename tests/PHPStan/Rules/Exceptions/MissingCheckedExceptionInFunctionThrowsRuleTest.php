<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInFunctionThrowsRule>
 */
class MissingCheckedExceptionInFunctionThrowsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInFunctionThrowsRule(
			new MissingCheckedExceptionInThrowsCheck(new ExceptionTypeResolver(
				$this->createReflectionProvider(),
				[],
				[\PHPStan\ShouldNotHappenException::class]
			))
		);
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/missing-exception-function-throws.php';
		$this->analyse([__DIR__ . '/data/missing-exception-function-throws.php'], [
			[
				'Function MissingExceptionFunctionThrows\doBaz() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				18,
			],
			[
				'Function MissingExceptionFunctionThrows\doLorem() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				24,
			],
			[
				'Function MissingExceptionFunctionThrows\doLorem2() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				29,
			],
		]);
	}

}
