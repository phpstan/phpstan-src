<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<TooWidePropertyHookThrowTypeRule>
 */
class TooWidePropertyHookThrowTypeRuleTest extends RuleTestCase
{

	private bool $implicitThrows = true;

	protected function getRule(): Rule
	{
		return new TooWidePropertyHookThrowTypeRule(self::getContainer()->getByType(FileTypeMapper::class), new TooWideThrowTypeCheck($this->implicitThrows));
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/too-wide-throws-property-hook.php'], [
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$d has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				33,
			],
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$g has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				58,
			],
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$h has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				68,
			],
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$j has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				76,
			],
		]);
	}

}
