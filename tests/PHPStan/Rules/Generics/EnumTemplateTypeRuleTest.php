<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EnumTemplateTypeRule>
 */
class EnumTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EnumTemplateTypeRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/enum-template.php'], [
			[
				'Enum EnumTemplate\Foo has PHPDoc @template tag but enums cannot be generic.',
				8,
			],
			[
				'Enum EnumTemplate\Bar has PHPDoc @template tags but enums cannot be generic.',
				17,
			],
		]);
	}

}
