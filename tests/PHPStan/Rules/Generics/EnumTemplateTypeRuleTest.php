<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<EnumTemplateTypeRule>
 */
class EnumTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EnumTemplateTypeRule();
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
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
