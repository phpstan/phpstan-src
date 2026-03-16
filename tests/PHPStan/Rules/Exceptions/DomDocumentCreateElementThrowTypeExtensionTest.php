<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInMethodThrowsRule>
 */
class DomDocumentCreateElementThrowTypeExtensionTest extends RuleTestCase
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

}
