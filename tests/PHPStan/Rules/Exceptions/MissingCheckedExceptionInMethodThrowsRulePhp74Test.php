<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInMethodThrowsRule>
 */
class MissingCheckedExceptionInMethodThrowsRulePhp74Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// Error is not unchecked so that the result depends only on the @throws tags in the stubs
		return new MissingCheckedExceptionInMethodThrowsRule(
			new MissingCheckedExceptionInThrowsCheck(new DefaultExceptionTypeResolver(
				self::createReflectionProvider(),
				[],
				[],
				[],
				[],
			)),
		);
	}

	public function testInternalErrors(): void
	{
		// the internal functions and methods throw ValueError and ArgumentCountError only as of PHP 8.0
		$this->analyse([__DIR__ . '/data/missing-exception-method-throws-internal-errors.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/missing-exception-method-throws-php74.neon',
		];
	}

}
