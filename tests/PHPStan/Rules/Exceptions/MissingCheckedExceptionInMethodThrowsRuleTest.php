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

	public function testBugArrayOffset(): void
	{
		$this->analyse([__DIR__ . '/data/bug-array-offset.php'], [
			[
				"Method BugArrayOffset\Foo::__construct() throws checked exception BugArrayOffset\ParameterNotFoundException but it's missing from the PHPDoc @throws tag.",
				19,
			],
			[
				"Method BugArrayOffset\Foo2::__construct() throws checked exception BugArrayOffset\ParameterNotFoundException but it's missing from the PHPDoc @throws tag.",
				27,
			],
			[
				"Method BugArrayOffset\Foo3::__construct() throws checked exception BugArrayOffset\ParameterNotFoundException but it's missing from the PHPDoc @throws tag.",
				35,
			],
			[
				"Method BugArrayOffset\Foo4::__construct() throws checked exception BugArrayOffset\ParameterNotFoundException but it's missing from the PHPDoc @throws tag.",
				43,
			],
		]);
	}

	public function testBug13792(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13792.php'], [
			[
				'Method Bug13792\Foo::dynamicName() throws checked exception DOMException but it\'s missing from the PHPDoc @throws tag.',
				20,
			],
			[
				'Method Bug13792\Foo::invalidConstantName() throws checked exception DOMException but it\'s missing from the PHPDoc @throws tag.',
				25,
			],
			[
				'Method Bug13792\Foo::unions() throws checked exception DOMException but it\'s missing from the PHPDoc @throws tag.',
				35,
			],
		]);
	}

	public function testConditionalThrows(): void
	{
		$this->analyse([__DIR__ . '/data/conditional-throws-method.php'], [
			[
				'Method ConditionalThrowsMethod\Caller::methodCallZero() throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				81,
			],
			[
				'Method ConditionalThrowsMethod\Caller::staticCallZero() throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				93,
			],
			[
				'Method ConditionalThrowsMethod\Caller::constructorZero() throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				105,
			],
			[
				'Method ConditionalThrowsMethod\Caller::lookupString() throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				123,
			],
			[
				'Method ConditionalThrowsMethod\Caller::inheritedMethodCallZero() throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				129,
			],
		]);
	}

	public function testGenericThrows(): void
	{
		$this->analyse([__DIR__ . '/data/generic-throws-9497.php'], []);
	}

}
