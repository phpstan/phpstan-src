<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInMethodThrowsRule>
 */
class MissingCheckedExceptionInMethodThrowsRulePhp80Test extends RuleTestCase
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
		$this->analyse([__DIR__ . '/data/missing-exception-method-throws-internal-errors.php'], [
			[
				'Method MissingExceptionMethodThrowsInternalErrors\Foo::doFoo() throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag.',
				10,
			],
			[
				'Method MissingExceptionMethodThrowsInternalErrors\Foo::doFoo() throws checked exception ArgumentCountError but it\'s missing from the PHPDoc @throws tag.',
				14,
			],
			[
				'Method MissingExceptionMethodThrowsInternalErrors\Foo::doFoo() throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag.',
				14,
			],
			[
				'Method MissingExceptionMethodThrowsInternalErrors\Foo::doBar() throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag.',
				19,
			],
			[
				'Method MissingExceptionMethodThrowsInternalErrors\Foo::doBar() throws checked exception ValueError but it\'s missing from the PHPDoc @throws tag.',
				20,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/missing-exception-method-throws-php80.neon',
		];
	}

}
