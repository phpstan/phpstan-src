<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\RuleTestCase;
use function sprintf;
use const PHP_VERSION_ID;

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
		$errors = [
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
				sprintf(
					'Method MissingExceptionMethodThrows\Foo::dateTimeZoneDoesThrows() throws checked exception %s but it\'s missing from the PHPDoc @throws tag.',
					PHP_VERSION_ID >= 80300 ? 'DateInvalidTimeZoneException' : 'Exception',
				),
				95,
			],
			[
				sprintf(
					'Method MissingExceptionMethodThrows\Foo::dateIntervalDoesThrows() throws checked exception %s but it\'s missing from the PHPDoc @throws tag.',
					PHP_VERSION_ID >= 80300 ? 'DateMalformedIntervalStringException' : 'Exception',
				),
				105,
			],
		];
		if (PHP_VERSION_ID >= 80300) {
			$errors[] = [
				'Method MissingExceptionMethodThrows\Foo::dateTimeModifyDoesThrows() throws checked exception DateMalformedStringException but it\'s missing from the PHPDoc @throws tag.',
				121,
			];
			$errors[] = [
				'Method MissingExceptionMethodThrows\Foo::dateTimeModifyDoesThrows() throws checked exception DateMalformedStringException but it\'s missing from the PHPDoc @throws tag.',
				122,
			];
		}
		$this->analyse([__DIR__ . '/data/missing-exception-method-throws.php'], $errors);
	}

}
