<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInMethodThrowsRule>
 */
class MissingCheckedExceptionInMethodThrowsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInMethodThrowsRule(
			new MissingCheckedExceptionInThrowsCheck(new DefaultExceptionTypeResolver(
				$this->createReflectionProvider(),
				[],
				[ShouldNotHappenException::class],
				[],
				[],
			)),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-exception-method-throws.php'], [
			[
				'Method MissingExceptionMethodThrows\Foo::doBaz() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				23,
			],
			[
				'Method MissingExceptionMethodThrows\Foo::doLorem() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				29,
			],
			[
				'Method MissingExceptionMethodThrows\Foo::doLorem2() throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				34,
			],
			[
				'Method MissingExceptionMethodThrows\Foo::dateTimeZoneDoesThrows() throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				95,
			],
		]);
	}

}
