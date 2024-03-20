<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<TooWideMethodThrowTypeRule>
 */
class TooWideMethodThrowTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TooWideMethodThrowTypeRule(self::getContainer()->getByType(FileTypeMapper::class), new TooWideThrowTypeCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/too-wide-throws-method.php'], [
			[
				'Method TooWideThrowsMethod\Foo::doFoo4() has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				29,
			],
			[
				'Method TooWideThrowsMethod\Foo::doFoo7() has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				51,
			],
			[
				'Method TooWideThrowsMethod\Foo::doFoo8() has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				60,
			],
			[
				'Method TooWideThrowsMethod\Foo::doFoo9() has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				66,
			],
			[
				'Method TooWideThrowsMethod\ParentClass::doFoo() has LogicException in PHPDoc @throws tag but it\'s not thrown.',
				77,
			],
			[
				'Method TooWideThrowsMethod\ImmediatelyCalledCallback::doFoo2() has InvalidArgumentException in PHPDoc @throws tag but it\'s not thrown.',
				167,
			],
		]);
	}

	public function testBug6233(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6233.php'], []);
	}

	public function testImmediatelyCalledArrowFunction(): void
	{
		if (PHP_VERSION_ID < 70400) {
			self::markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/immediately-called-arrow-function.php'], [
			[
				'Method ImmediatelyCalledArrowFunction\ImmediatelyCalledCallback::doFoo2() has InvalidArgumentException in PHPDoc @throws tag but it\'s not thrown.',
				19,
			],
		]);
	}

	public function testFirstClassCallable(): void
	{
		if (PHP_VERSION_ID < 80100) {
			self::markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/immediately-called-fcc.php'], []);
	}

}
